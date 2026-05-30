<?php

namespace Tests\Unit;

use App\Domains\Tasks\Enums\ScheduleRecurrenceType;
use App\Domains\Tasks\Requests\UpdateTaskEventScheduleRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateTaskEventScheduleRequestTest extends TestCase
{
    /** @test */
    public function it_allows_null_for_prohibited_fields_on_update()
    {
        $request = new UpdateTaskEventScheduleRequest();
        $request->merge([
            'recurrence_type' => ScheduleRecurrenceType::WEEKLY,
            'day_of_month' => null,
            'days_of_week' => [1, 2],
        ]);

        $rules = $request->rules();
        $validator = Validator::make($request->all(), $rules);

        $this->assertFalse($validator->fails(), 'Validation should pass when day_of_month is null for WEEKLY');
    }

    /** @test */
    public function it_prohibits_non_null_values_for_prohibited_fields_on_update()
    {
        $request = new UpdateTaskEventScheduleRequest();
        $request->merge([
            'recurrence_type' => ScheduleRecurrenceType::WEEKLY,
            'day_of_month' => 15,
            'days_of_week' => [1, 2],
        ]);

        $rules = $request->rules();
        $validator = Validator::make($request->all(), $rules);

        $this->assertTrue($validator->fails(), 'Validation should fail when day_of_month is provided for WEEKLY');
        $this->assertArrayHasKey('day_of_month', $validator->errors()->toArray());
    }

    /** @test */
    public function it_uses_recurrence_type_from_route_if_not_present_in_input()
    {
        $route = $this->createMock(\Illuminate\Routing\Route::class);
        $route->method('parameter')->with('schedule')->willReturn((object)['recurrence_type' => ScheduleRecurrenceType::WEEKLY]);

        $request = new UpdateTaskEventScheduleRequest();
        $request->setRouteResolver(function() use ($route) {
            return $route;
        });

        $request->merge([
            'day_of_month' => null,
            'days_of_week' => [1, 2],
        ]);

        $rules = $request->rules();
        $validator = Validator::make($request->all(), $rules);

        $this->assertFalse($validator->fails(), 'Validation should pass when using recurrence_type from route and day_of_month is null');
    }
}
