<?php

namespace Tests\Unit;

use App\Domains\Tasks\Enums\ScheduleRecurrenceType;
use App\Domains\Tasks\Enums\TaskEventStatus;
use App\Domains\Tasks\Models\Task;
use App\Domains\Tasks\Models\TaskEvent;
use App\Domains\Tasks\Models\TaskEventSchedule;
use App\Domains\Tasks\Services\TaskEventScheduleService;
use App\Support\Query\QueryPaginator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Tests\TestCase;

class TaskEventScheduleServiceTest extends TestCase
{
    private TaskEventScheduleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $paginator = Mockery::mock(QueryPaginator::class);
        $this->service = new TaskEventScheduleService($paginator);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    // ─── resolveDates / generateEvents via reflection ─────────────────

    /**
     * Invoke private method for testing.
     */
    private function invokeResolveDates(TaskEventSchedule $schedule, Carbon $from, Carbon $until): array
    {
        $reflection = new \ReflectionMethod($this->service, 'resolveDates');
        $reflection->setAccessible(true);
        return $reflection->invoke($this->service, $schedule, $from, $until);
    }

    private function makeSchedule(array $attrs = []): TaskEventSchedule
    {
        $schedule = Mockery::mock(TaskEventSchedule::class)->makePartial();

        $defaults = [
            'id' => 1,
            'task_id' => 1,
            'user_id' => 1,
            'recurrence_type' => ScheduleRecurrenceType::DAILY,
            'days_of_week' => null,
            'day_of_month' => null,
            'time_start' => '09:00',
            'time_end' => '10:00',
            'description' => 'Test schedule',
            'starts_at' => Carbon::parse('2026-06-01'),
            'ends_at' => null,
            'horizon_generated_until' => Carbon::parse('2026-06-29'),
            'active' => true,
        ];

        $merged = array_merge($defaults, $attrs);

        foreach ($merged as $key => $value) {
            $schedule->{$key} = $value;
            $schedule->shouldReceive('getAttribute')->with($key)->andReturn($value)->byDefault();
        }

        return $schedule;
    }

    // ─── ONCE: resolveDates ──────────────────────────────────────────

    /** @test */
    public function once_resolve_dates_returns_single_date()
    {
        $schedule = $this->makeSchedule([
            'recurrence_type' => ScheduleRecurrenceType::ONCE,
            'starts_at' => Carbon::parse('2026-06-15'),
        ]);

        $dates = $this->invokeResolveDates(
            $schedule,
            Carbon::parse('2026-06-01'),
            Carbon::parse('2026-06-30')
        );

        $this->assertCount(1, $dates);
        $this->assertEquals('2026-06-15', $dates[0]->toDateString());
    }

    /** @test */
    public function once_resolve_dates_returns_empty_when_out_of_range()
    {
        $schedule = $this->makeSchedule([
            'recurrence_type' => ScheduleRecurrenceType::ONCE,
            'starts_at' => Carbon::parse('2026-07-15'),
        ]);

        $dates = $this->invokeResolveDates(
            $schedule,
            Carbon::parse('2026-06-01'),
            Carbon::parse('2026-06-30')
        );

        $this->assertCount(0, $dates);
    }

    // ─── DAILY: resolveDates ─────────────────────────────────────────

    /** @test */
    public function daily_resolve_dates_returns_correct_count()
    {
        $schedule = $this->makeSchedule([
            'recurrence_type' => ScheduleRecurrenceType::DAILY,
        ]);

        $from = Carbon::parse('2026-06-01');
        $until = Carbon::parse('2026-06-08');

        $dates = $this->invokeResolveDates($schedule, $from, $until);

        $this->assertCount(8, $dates); // 01,02,...,08 inclusive of start, lt until already handled
    }

    // ─── WEEKLY: resolveDates ────────────────────────────────────────

    /** @test */
    public function weekly_resolve_dates_respects_days_of_week()
    {
        $schedule = $this->makeSchedule([
            'recurrence_type' => ScheduleRecurrenceType::WEEKLY,
            'days_of_week' => [1, 3], // Monday and Wednesday
        ]);

        $from = Carbon::parse('2026-06-01'); // Monday
        $until = Carbon::parse('2026-06-15');

        $dates = $this->invokeResolveDates($schedule, $from, $until);

        foreach ($dates as $date) {
            // days_of_week [1,3] = Monday+1=Tuesday(2), Monday+3=Thursday(4) in ISO
            $this->assertContains($date->dayOfWeekIso, [2, 4]);
        }
    }

    /** @test */
    public function weekly_resolve_dates_returns_empty_when_no_days_specified()
    {
        $schedule = $this->makeSchedule([
            'recurrence_type' => ScheduleRecurrenceType::WEEKLY,
            'days_of_week' => [],
        ]);

        $dates = $this->invokeResolveDates(
            $schedule,
            Carbon::parse('2026-06-01'),
            Carbon::parse('2026-06-30')
        );

        $this->assertEmpty($dates);
    }

    // ─── MONTHLY: resolveDates ───────────────────────────────────────

    /** @test */
    public function monthly_resolve_dates_uses_day_of_month()
    {
        $schedule = $this->makeSchedule([
            'recurrence_type' => ScheduleRecurrenceType::MONTHLY,
            'day_of_month' => 15,
        ]);

        $from = Carbon::parse('2026-06-01');
        $until = Carbon::parse('2026-09-01');

        $dates = $this->invokeResolveDates($schedule, $from, $until);

        $this->assertCount(3, $dates); // Jun 15, Jul 15, Aug 15
        foreach ($dates as $date) {
            $this->assertEquals(15, $date->day);
        }
    }

    /** @test */
    public function monthly_resolve_dates_clamps_to_month_end()
    {
        $schedule = $this->makeSchedule([
            'recurrence_type' => ScheduleRecurrenceType::MONTHLY,
            'day_of_month' => 31,
        ]);

        $from = Carbon::parse('2026-02-01');
        $until = Carbon::parse('2026-04-01');

        $dates = $this->invokeResolveDates($schedule, $from, $until);

        // Feb has 28 days, Mar has 31
        $this->assertTrue($dates[0]->day <= 28); // Feb clamped
        $this->assertEquals(31, $dates[1]->day); // Mar 31
    }

    // ─── detectChangedFields ────────────────────────────────────────

    /** @test */
    public function detect_changed_fields_finds_structural_changes()
    {
        $method = new \ReflectionMethod($this->service, 'detectChangedFields');
        $method->setAccessible(true);

        $oldValues = [
            'recurrence_type' => ScheduleRecurrenceType::WEEKLY,
            'starts_at' => '2026-06-01',
            'ends_at' => null,
            'days_of_week' => [1, 3],
            'day_of_month' => null,
            'time_start' => '09:00',
            'time_end' => '10:00',
            'description' => 'Test',
        ];

        $schedule = $this->makeSchedule([
            'recurrence_type' => ScheduleRecurrenceType::ONCE,
            'starts_at' => Carbon::parse('2026-06-01'),
            'ends_at' => null,
            'days_of_week' => [1, 3],
            'day_of_month' => null,
            'time_start' => '09:00',
            'time_end' => '10:00',
            'description' => 'Test',
        ]);

        $changed = $method->invoke($this->service, $oldValues, $schedule);

        $this->assertContains('recurrence_type', $changed);
        $this->assertNotContains('time_start', $changed);
        $this->assertNotContains('description', $changed);
    }

    /** @test */
    public function detect_changed_fields_finds_time_changes()
    {
        $method = new \ReflectionMethod($this->service, 'detectChangedFields');
        $method->setAccessible(true);

        $oldValues = [
            'recurrence_type' => ScheduleRecurrenceType::DAILY,
            'starts_at' => '2026-06-01',
            'ends_at' => null,
            'days_of_week' => null,
            'day_of_month' => null,
            'time_start' => '09:00',
            'time_end' => '10:00',
            'description' => 'Test',
        ];

        $schedule = $this->makeSchedule([
            'recurrence_type' => ScheduleRecurrenceType::DAILY,
            'starts_at' => Carbon::parse('2026-06-01'),
            'ends_at' => null,
            'days_of_week' => null,
            'day_of_month' => null,
            'time_start' => '10:00', // changed
            'time_end' => '11:00',   // changed
            'description' => 'Test',
        ]);

        $changed = $method->invoke($this->service, $oldValues, $schedule);

        $this->assertContains('time_start', $changed);
        $this->assertContains('time_end', $changed);
        $this->assertNotContains('recurrence_type', $changed);
    }

    /** @test */
    public function detect_changed_fields_handles_array_comparison()
    {
        $method = new \ReflectionMethod($this->service, 'detectChangedFields');
        $method->setAccessible(true);

        $oldValues = [
            'recurrence_type' => ScheduleRecurrenceType::WEEKLY,
            'starts_at' => '2026-06-01',
            'ends_at' => null,
            'days_of_week' => [1, 3],
            'day_of_month' => null,
            'time_start' => '09:00',
            'time_end' => '10:00',
            'description' => 'Test',
        ];

        // Same values in different order should not be detected as changed
        $schedule = $this->makeSchedule([
            'recurrence_type' => ScheduleRecurrenceType::WEEKLY,
            'starts_at' => Carbon::parse('2026-06-01'),
            'ends_at' => null,
            'days_of_week' => [3, 1], // same values, different order
            'day_of_month' => null,
            'time_start' => '09:00',
            'time_end' => '10:00',
            'description' => 'Test',
        ]);

        $changed = $method->invoke($this->service, $oldValues, $schedule);

        $this->assertNotContains('days_of_week', $changed);
    }

    /** @test */
    public function detect_changed_fields_detects_array_content_change()
    {
        $method = new \ReflectionMethod($this->service, 'detectChangedFields');
        $method->setAccessible(true);

        $oldValues = [
            'recurrence_type' => ScheduleRecurrenceType::WEEKLY,
            'starts_at' => '2026-06-01',
            'ends_at' => null,
            'days_of_week' => [1, 3],
            'day_of_month' => null,
            'time_start' => '09:00',
            'time_end' => '10:00',
            'description' => 'Test',
        ];

        $schedule = $this->makeSchedule([
            'recurrence_type' => ScheduleRecurrenceType::WEEKLY,
            'starts_at' => Carbon::parse('2026-06-01'),
            'ends_at' => null,
            'days_of_week' => [1, 4], // actually changed
            'day_of_month' => null,
            'time_start' => '09:00',
            'time_end' => '10:00',
            'description' => 'Test',
        ]);

        $changed = $method->invoke($this->service, $oldValues, $schedule);

        $this->assertContains('days_of_week', $changed);
    }

    /** @test */
    public function detect_no_changes_returns_empty()
    {
        $method = new \ReflectionMethod($this->service, 'detectChangedFields');
        $method->setAccessible(true);

        $oldValues = [
            'recurrence_type' => ScheduleRecurrenceType::DAILY,
            'starts_at' => '2026-06-01',
            'ends_at' => null,
            'days_of_week' => null,
            'day_of_month' => null,
            'time_start' => '09:00',
            'time_end' => '10:00',
            'description' => 'Test',
        ];

        $schedule = $this->makeSchedule([
            'recurrence_type' => ScheduleRecurrenceType::DAILY,
            'starts_at' => Carbon::parse('2026-06-01'),
            'ends_at' => null,
            'days_of_week' => null,
            'day_of_month' => null,
            'time_start' => '09:00',
            'time_end' => '10:00',
            'description' => 'Test',
        ]);

        $changed = $method->invoke($this->service, $oldValues, $schedule);

        $this->assertEmpty($changed);
    }

    // ─── hasFieldsInSet ─────────────────────────────────────────────

    /** @test */
    public function has_fields_in_set_returns_true_when_match()
    {
        $method = new \ReflectionMethod($this->service, 'hasFieldsInSet');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, ['recurrence_type', 'time_start'], ['recurrence_type', 'starts_at']);

        $this->assertTrue($result);
    }

    /** @test */
    public function has_fields_in_set_returns_false_when_no_match()
    {
        $method = new \ReflectionMethod($this->service, 'hasFieldsInSet');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, ['time_start', 'description'], ['recurrence_type', 'starts_at']);

        $this->assertFalse($result);
    }

    // ─── ScheduleRecurrenceType enum ────────────────────────────────

    /** @test */
    public function once_is_valid_recurrence_type()
    {
        $this->assertTrue(ScheduleRecurrenceType::isValid('ONCE'));
    }

    /** @test */
    public function all_recurrence_types_include_once()
    {
        $this->assertContains('ONCE', ScheduleRecurrenceType::values());
    }

    // ─── snapshotRelevantFields ─────────────────────────────────────

    /** @test */
    public function snapshot_captures_all_relevant_fields()
    {
        $method = new \ReflectionMethod($this->service, 'snapshotRelevantFields');
        $method->setAccessible(true);

        $schedule = $this->makeSchedule([
            'recurrence_type' => ScheduleRecurrenceType::WEEKLY,
            'starts_at' => Carbon::parse('2026-06-01'),
            'ends_at' => Carbon::parse('2026-12-31'),
            'days_of_week' => [1, 3, 5],
            'day_of_month' => null,
            'time_start' => '09:00',
            'time_end' => '10:30',
            'description' => 'Snapshot test',
        ]);

        $snapshot = $method->invoke($this->service, $schedule);

        $this->assertEquals(ScheduleRecurrenceType::WEEKLY, $snapshot['recurrence_type']);
        $this->assertEquals('2026-06-01', $snapshot['starts_at']);
        $this->assertEquals('2026-12-31', $snapshot['ends_at']);
        $this->assertEquals([1, 3, 5], $snapshot['days_of_week']);
        $this->assertNull($snapshot['day_of_month']);
        $this->assertEquals('09:00', $snapshot['time_start']);
        $this->assertEquals('10:30', $snapshot['time_end']);
        $this->assertEquals('Snapshot test', $snapshot['description']);
    }

    /** @test */
    public function snapshot_converts_carbon_to_date_string()
    {
        $method = new \ReflectionMethod($this->service, 'snapshotRelevantFields');
        $method->setAccessible(true);

        $schedule = $this->makeSchedule([
            'starts_at' => Carbon::parse('2026-06-15 10:30:00'),
        ]);

        $snapshot = $method->invoke($this->service, $schedule);

        $this->assertEquals('2026-06-15', $snapshot['starts_at']);
        $this->assertIsString($snapshot['starts_at']);
    }

    // ─── resolveDates respects ends_at ──────────────────────────────

    /** @test */
    public function resolve_dates_clamps_until_to_ends_at()
    {
        $schedule = $this->makeSchedule([
            'recurrence_type' => ScheduleRecurrenceType::DAILY,
            'ends_at' => Carbon::parse('2026-06-05'),
        ]);

        $from = Carbon::parse('2026-06-01');
        $until = Carbon::parse('2026-06-30'); // Way beyond ends_at

        $dates = $this->invokeResolveDates($schedule, $from, $until);

        // Should clamp to June 5 (lt comparison), so 01-05 = 5 days
        foreach ($dates as $date) {
            $this->assertTrue($date->lte(Carbon::parse('2026-06-05')));
        }
    }
}
