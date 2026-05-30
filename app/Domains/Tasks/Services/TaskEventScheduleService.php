<?php

namespace App\Domains\Tasks\Services;

use App\Domains\Tasks\Enums\ScheduleRecurrenceType;
use App\Domains\Tasks\Enums\TaskEventStatus;
use App\Domains\Tasks\Models\Task;
use App\Domains\Tasks\Models\TaskEventSchedule;
use App\Domains\Users\Models\User;
use App\Helpers\ApiResponse;
use App\Support\Query\QueryPaginator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TaskEventScheduleService
{
    private const HORIZON_WEEKS = 4;

    /**
     * Fields that define the recurrence structure.
     * Changes to these require full regeneration of future events.
     */
    private const STRUCTURAL_FIELDS = [
        'recurrence_type',
        'starts_at',
        'ends_at',
        'days_of_week',
        'day_of_month',
    ];

    /**
     * Fields that define time slots.
     * Changes to these can be applied in-place without regeneration.
     */
    private const TIME_FIELDS = [
        'time_start',
        'time_end',
    ];

    private QueryPaginator $paginator;

    public function __construct(QueryPaginator $paginator)
    {
        $this->paginator = $paginator;
    }

    public function paginate(array $data, Task $task)
    {
        /** @var User $user */
        $user = auth()->user();

        $task = Task::query()->ownedByUser($user)->findOrFail($task->id);

        $query = $task->eventSchedules()
            ->with('events')
            ->orderBy('created_at', 'desc')
            ->getQuery();

        $schedules = $this->paginator->paginate($query, $data);

        return ApiResponse::success($schedules);
    }

    public function show(Task $task, TaskEventSchedule $schedule)
    {
        /** @var User $user */
        $user = auth()->user();

        Task::query()->ownedByUser($user)->findOrFail($task->id);

        $schedule->load('events');

        return ApiResponse::success($schedule);
    }

    public function create(Task $task, array $data)
    {
        /** @var User $user */
        $user = auth()->user();

        $task = Task::query()->ownedByUser($user)->findOrFail($task->id);

        return DB::transaction(function () use ($task, $user, $data) {
            $startsAt = Carbon::parse($data['starts_at']);

            $data['user_id'] = $user->id;

            if ($data['recurrence_type'] === ScheduleRecurrenceType::ONCE) {
                $data['ends_at'] = $startsAt->toDateString();
                $data['horizon_generated_until'] = $startsAt->toDateString();
                $data['days_of_week'] = null;
                $data['day_of_month'] = null;
            } else {
                $data['horizon_generated_until'] = $this->calculateHorizonDate($startsAt, $data['ends_at'] ?? null)->toDateString();
            }

            $schedule = $task->eventSchedules()->create($data);

            $this->generateEvents(
                $schedule,
                $startsAt,
                Carbon::parse($data['horizon_generated_until'])
            );

            return ApiResponse::success($schedule->fresh('events'));
        });
    }

    public function update(Task $task, TaskEventSchedule $schedule, array $data)
    {
        /** @var User $user */
        $user = auth()->user();

        Task::query()->ownedByUser($user)->findOrFail($task->id);

        return DB::transaction(function () use ($schedule, $data) {
            $oldValues = $this->snapshotRelevantFields($schedule);
            $oldDescription = $schedule->description;

            $schedule->update($data);
            $schedule->refresh();

            $changedFields = $this->detectChangedFields($oldValues, $schedule);

            $now = Carbon::now();

            $futureAutoEvents = $schedule->events()
                ->where('status', TaskEventStatus::PENDING)
                ->where('scheduled_at', '>', $now)
                ->where('is_manually_edited', false);

            $hasStructuralChange = $this->hasFieldsInSet($changedFields, self::STRUCTURAL_FIELDS);
            $hasTimeChange = $this->hasFieldsInSet($changedFields, self::TIME_FIELDS);
            $hasDescriptionChange = in_array('description', $changedFields);

            if ($hasStructuralChange) {
                $this->handleStructuralChange($schedule, $futureAutoEvents, $now);
            } elseif ($hasTimeChange) {
                $this->handleTimeChange($schedule, $futureAutoEvents, $oldValues);
            }

            if ($hasDescriptionChange && !$hasStructuralChange) {
                $this->propagateDescription($schedule, $oldDescription, $now);
            }

            return ApiResponse::success($schedule->fresh('events'));
        });
    }

    public function delete(Task $task, TaskEventSchedule $schedule)
    {
        /** @var User $user */
        $user = auth()->user();

        Task::query()->ownedByUser($user)->findOrFail($task->id);

        $schedule->events()
            ->where('status', TaskEventStatus::PENDING)
            ->where('scheduled_at', '>', Carbon::now())
            ->delete();

        $schedule->delete();

        return ApiResponse::success();
    }

    /**
     * Extend horizons for all active recurring schedules. Called by the artisan command.
     * Skips ONCE schedules since they don't have future horizons.
     */
    public function extendAllHorizons(): int
    {
        $thresholdDate = Carbon::now()->addDays(7)->toDateString();

        $schedules = TaskEventSchedule::query()
            ->needsExtension($thresholdDate)
            ->get();

        foreach ($schedules as $schedule) {
            $from = $schedule->horizon_generated_until->copy()->addDay();
            $horizonUntil = $this->calculateHorizonDate(Carbon::now(), $schedule->ends_at);

            if ($from->lte($horizonUntil)) {
                $this->generateEvents($schedule, $from, $horizonUntil);
                $schedule->update(['horizon_generated_until' => $horizonUntil->toDateString()]);
            }
        }

        return $schedules->count();
    }

    /**
     * Generate task_events for a schedule between two dates.
     */
    public function generateEvents(TaskEventSchedule $schedule, Carbon $from, Carbon $until): void
    {
        $dates = $this->resolveDates($schedule, $from, $until);

        foreach ($dates as $date) {
            $scheduledAt = $date->copy()->setTimeFromTimeString($schedule->time_start);
            $endsAt = $schedule->time_end
                ? $date->copy()->setTimeFromTimeString($schedule->time_end)
                : null;

            $schedule->events()->create([
                'task_id' => $schedule->task_id,
                'user_id' => $schedule->user_id,
                'task_event_schedule_id' => $schedule->id,
                'description' => $schedule->description,
                'status' => TaskEventStatus::PENDING,
                'scheduled_at' => $scheduledAt,
                'ends_at' => $endsAt,
                'is_manually_edited' => false,
            ]);
        }
    }

    // ─── Private helpers ────────────────────────────────────────────────

    /**
     * Handle structural changes: delete auto-generated future PENDING events and regenerate.
     */
    private function handleStructuralChange(
        TaskEventSchedule $schedule,
        $futureAutoEventsQuery,
        Carbon $now
    ): void {
        (clone $futureAutoEventsQuery)->delete();

        if ($schedule->recurrence_type === ScheduleRecurrenceType::ONCE) {
            $startsAt = Carbon::parse($schedule->starts_at);
            $schedule->update([
                'ends_at' => $startsAt->toDateString(),
                'horizon_generated_until' => $startsAt->toDateString(),
                'days_of_week' => null,
                'day_of_month' => null,
            ]);
            $this->generateEvents($schedule->fresh(), $startsAt, $startsAt->copy()->addDay());
        } else {
            $horizonUntil = $this->calculateHorizonDate($now, $schedule->ends_at);
            $this->generateEvents($schedule->fresh(), $now, $horizonUntil);
            $schedule->update(['horizon_generated_until' => $horizonUntil->toDateString()]);
        }
    }

    /**
     * Handle time-only changes: update scheduled_at/ends_at in-place for auto-generated future events.
     */
    private function handleTimeChange(
        TaskEventSchedule $schedule,
        $futureAutoEventsQuery,
        array $oldValues
    ): void {
        $events = (clone $futureAutoEventsQuery)->get();

        foreach ($events as $event) {
            $updates = [];

            if ($schedule->time_start !== ($oldValues['time_start'] ?? null)) {
                $updates['scheduled_at'] = $event->scheduled_at
                    ->copy()
                    ->setTimeFromTimeString($schedule->time_start);
            }

            if (array_key_exists('time_end', $oldValues)) {
                $updates['ends_at'] = $schedule->time_end
                    ? $event->scheduled_at->copy()->startOfDay()->setTimeFromTimeString($schedule->time_end)
                    : null;
            }

            if (!empty($updates)) {
                $event->update($updates);
            }
        }
    }

    /**
     * Propagate description change to future PENDING auto-generated events
     * that still have the old description (not manually edited).
     */
    private function propagateDescription(
        TaskEventSchedule $schedule,
        ?string $oldDescription,
        Carbon $now
    ): void {
        $schedule->events()
            ->where('status', TaskEventStatus::PENDING)
            ->where('scheduled_at', '>', $now)
            ->where('is_manually_edited', false)
            ->where('description', $oldDescription)
            ->update(['description' => $schedule->description]);
    }

    /**
     * Take a snapshot of the schedule's relevant fields before update.
     */
    private function snapshotRelevantFields(TaskEventSchedule $schedule): array
    {
        $fields = array_merge(self::STRUCTURAL_FIELDS, self::TIME_FIELDS, ['description']);
        $snapshot = [];
        foreach ($fields as $field) {
            $value = $schedule->getAttribute($field);
            if ($value instanceof Carbon) {
                $snapshot[$field] = $value->toDateString();
            } elseif (is_array($value)) {
                $snapshot[$field] = $value;
            } else {
                $snapshot[$field] = $value;
            }
        }
        return $snapshot;
    }

    /**
     * Detect which relevant fields changed between old snapshot and current schedule.
     *
     * @return string[]
     */
    private function detectChangedFields(array $oldValues, TaskEventSchedule $schedule): array
    {
        $changed = [];

        foreach ($oldValues as $field => $oldValue) {
            $newValue = $schedule->getAttribute($field);

            if ($newValue instanceof Carbon) {
                $newValue = $newValue->toDateString();
            }

            if (is_array($oldValue) && is_array($newValue)) {
                sort($oldValue);
                $sortedNew = $newValue;
                sort($sortedNew);
                if ($oldValue !== $sortedNew) {
                    $changed[] = $field;
                }
            } elseif ($oldValue != $newValue) {
                $changed[] = $field;
            }
        }

        return $changed;
    }

    /**
     * Check if any of the changed fields belong to the given set.
     */
    private function hasFieldsInSet(array $changedFields, array $set): bool
    {
        return !empty(array_intersect($changedFields, $set));
    }

    /**
     * Resolve concrete dates from a schedule's recurrence rule.
     *
     * @return Carbon[]
     */
    private function resolveDates(TaskEventSchedule $schedule, Carbon $from, Carbon $until): array
    {
        $endsAt = $schedule->ends_at;
        if ($endsAt && $until->gt($endsAt)) {
            $until = $endsAt->copy();
        }

        $dates = [];

        switch ($schedule->recurrence_type) {
            case ScheduleRecurrenceType::ONCE:
                $candidate = Carbon::parse($schedule->starts_at)->startOfDay();
                if ($candidate->gte($from->copy()->startOfDay()) && $candidate->lte($until)) {
                    $dates[] = $candidate;
                }
                break;

            case ScheduleRecurrenceType::DAILY:
                $cursor = $from->copy()->startOfDay();
                while ($cursor->lte($until)) {
                    $dates[] = $cursor->copy();
                    $cursor->addDay();
                }
                break;

            case ScheduleRecurrenceType::WEEKLY:
                $daysOfWeek = $schedule->days_of_week ?? [];
                if (empty($daysOfWeek)) {
                    break;
                }
                $cursor = $from->copy()->startOfWeek(Carbon::MONDAY);
                while ($cursor->lte($until)) {
                    foreach ($daysOfWeek as $dow) {
                        $candidate = $cursor->copy()->startOfWeek(Carbon::MONDAY)->addDays($dow);
                        if ($candidate->gte($from) && $candidate->lte($until)) {
                            $dates[] = $candidate;
                        }
                    }
                    $cursor->addWeek();
                }
                break;

            case ScheduleRecurrenceType::MONTHLY:
                $dayOfMonth = $schedule->day_of_month ?? 1;
                $cursor = $from->copy()->startOfMonth();
                while ($cursor->lte($until)) {
                    $daysInMonth = $cursor->daysInMonth;
                    $actualDay = min($dayOfMonth, $daysInMonth);
                    $candidate = $cursor->copy()->day($actualDay);
                    if ($candidate->gte($from) && $candidate->lte($until)) {
                        $dates[] = $candidate;
                    }
                    $cursor->addMonth();
                }
                break;
        }

        return $dates;
    }

    private function calculateHorizonDate(Carbon $from, $endsAt = null): Carbon
    {
        if ($from->eq(Carbon::parse($from)->startOfDay()) === false) {
            // Normalize
        }

        $horizon = $from->copy()->addWeeks(self::HORIZON_WEEKS);

        if ($endsAt) {
            $endsAtCarbon = $endsAt instanceof Carbon ? $endsAt : Carbon::parse($endsAt);
            if ($endsAtCarbon->lt($horizon)) {
                return $endsAtCarbon;
            }
        }

        return $horizon;
    }
}
