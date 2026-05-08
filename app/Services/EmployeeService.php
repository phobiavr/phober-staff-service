<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Collection;

class EmployeeService {
    public function all(): Collection {
        return Employee::all();
    }
}
