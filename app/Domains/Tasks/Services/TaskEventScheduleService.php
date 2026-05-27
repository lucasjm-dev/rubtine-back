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
            $horizonUntil = $this->calculateHorizonDate($startsAt, $data['ends_at'] ?? null);

            $data['user_id'] = $user->id;
            $data['horizon_generated_until'] = $horizonUntil->toDateString();

            $schedule = $task->eventSchedules()->create($data);

            $this->generateEvents($schedule, $startsAt, $horizonUntil);

            return ApiResponse::success($schedule->fresh('events'));
        });
    }

    public function update(Task $task, TaskEventSchedule $schedule, array $data)
    {
        /** @var User $user */
        $user = auth()->user();

        Task::query()->ownedByUser($user)->findOrFail($task->id);

        return DB::transaction(function () use ($schedule, $data) {
            $schedule->update($data);

            $now = Carbon::now();

            $schedule->events()
                ->where('status', TaskEventStatus::PENDING)
                ->where('scheduled_at', '>', $now)
                ->delete();

            $horizonUntil = $this->calculateHorizonDate($now, $schedule->ends_at);
            $this->generateEvents($schedule->fresh(), $now, $horizonUntil);

            $schedule->update(['horizon_generated_until' => $horizonUntil->toDateString()]);

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
     * Extend horizons for all active schedules. Called by the artisan command.
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
            ]);
        }
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
