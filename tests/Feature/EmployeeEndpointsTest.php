<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Session;
use Phobiavr\PhoberLaravelCommon\Testing\ClearsExistingRows;
use Tests\TestCase;

class EmployeeEndpointsTest extends TestCase
{
    use ClearsExistingRows;

    protected function setUp(): void
    {
        parent::setUp();

        // Session has non-cascading FKs to employees, so it must be cleared
        // before Employee or the delete below would violate the constraint.
        $this->clearExistingRows(Session::class, Employee::class);
    }

    public function test_lists_employees_with_their_serviced_session_stats(): void
    {
        $this->authorizeAuthServer();
        Employee::factory()->count(2)->create();

        $this->withToken('token')->getJson('/employees')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonStructure([
                '*' => ['id', 'full_name', 'first_name', 'last_name', 'serviced' => [
                    'in_a_day', 'minutes_in_a_day', 'in_a_week', 'minutes_in_a_week',
                    'in_a_month', 'minutes_in_a_month', 'total', 'minutes_total',
                ]],
            ]);
    }
}
