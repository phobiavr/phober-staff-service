<?php

namespace Tests\Unit\Services;

use App\Models\Employee;
use App\Models\Session;
use App\Services\EmployeeService;
use Phobiavr\PhoberLaravelCommon\Testing\ClearsExistingRows;
use Tests\TestCase;

class EmployeeServiceTest extends TestCase
{
    use ClearsExistingRows;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearExistingRows(Session::class, Employee::class);
    }

    public function test_returns_all_employees(): void
    {
        Employee::factory()->count(3)->create();

        $this->assertCount(3, app(EmployeeService::class)->all());
    }
}
