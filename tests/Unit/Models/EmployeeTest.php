<?php

namespace Tests\Unit\Models;

use App\Models\Employee;
use App\Models\Session;
use Phobiavr\PhoberLaravelCommon\Enums\SessionStatusEnum;
use Phobiavr\PhoberLaravelCommon\Testing\ClearsExistingRows;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use ClearsExistingRows;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearExistingRows(Session::class, Employee::class);
    }

    public function test_concatenates_first_and_last_name(): void
    {
        $employee = Employee::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);

        $this->assertSame('John Doe', $employee->full_name);
    }

    public function test_only_counts_active_and_finished_sessions_towards_serviced_totals(): void
    {
        $employee = Employee::factory()->create();

        Session::factory()->for($employee, 'servicedBy')->active()->create(['time' => 30]);
        Session::factory()->for($employee, 'servicedBy')->finished()->create(['time' => 15]);
        Session::factory()->for($employee, 'servicedBy')->create(['status' => SessionStatusEnum::QUEUE->value, 'time' => 60]);
        Session::factory()->for($employee, 'servicedBy')->canceled()->create(['time' => 60]);

        $this->assertSame(2, $employee->serviced_total);
        $this->assertSame(45, $employee->serviced_minutes_total);
    }

    public function test_scopes_serviced_counts_to_today_this_week_and_this_month(): void
    {
        $employee = Employee::factory()->create();

        Session::factory()->for($employee, 'servicedBy')->active()->create(['time' => 10, 'created_at' => now()]);
        Session::factory()->for($employee, 'servicedBy')->active()->create(['time' => 20, 'created_at' => now()->subWeeks(2)]);

        $this->assertSame(1, $employee->serviced_in_a_day);
        $this->assertSame(10, $employee->serviced_minutes_in_a_day);
        $this->assertSame(1, $employee->serviced_in_a_week);
        $this->assertSame(1, $employee->serviced_in_a_month);
    }
}
