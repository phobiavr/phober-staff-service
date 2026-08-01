<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Phobiavr\PhoberLaravelCommon\Enums\SessionStatusEnum;

class Employee extends Model {
    use HasFactory;

    public function sessions() {
        return $this->hasMany(Session::class, 'serviced_by');
    }

    private function activeSessions() {
        return $this->sessions()->whereIn('status', [
            SessionStatusEnum::ACTIVE->value,
            SessionStatusEnum::FINISHED->value,
        ]);
    }

    public function getServicedTotalAttribute(): int {
        return $this->activeSessions()->count();
    }

    public function getServicedMinutesTotalAttribute(): int {
        return (int) $this->activeSessions()->sum('time');
    }

    public function getServicedInADayAttribute(): int {
        return $this->activeSessions()
            ->whereDate('created_at', now())
            ->count();
    }

    public function getServicedMinutesInADayAttribute(): int {
        return (int) $this->activeSessions()
            ->whereDate('created_at', now())
            ->sum('time');
    }

    public function getServicedInAWeekAttribute(): int {
        return $this->activeSessions()
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();
    }

    public function getServicedMinutesInAWeekAttribute(): int {
        return (int) $this->activeSessions()
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('time');
    }

    public function getServicedInAMonthAttribute(): int {
        return $this->activeSessions()
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
    }

    public function getServicedMinutesInAMonthAttribute(): int {
        return (int) $this->activeSessions()
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('time');
    }

    public function getFullNameAttribute(): string {
        return "{$this->first_name} {$this->last_name}";
    }
}
