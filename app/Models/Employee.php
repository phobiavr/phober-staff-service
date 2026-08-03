<?php

namespace App\Models;

use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Phobiavr\PhoberLaravelCommon\Enums\SessionStatusEnum;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $full_name
 * @property-read int $serviced_total
 * @property-read int $serviced_minutes_total
 * @property-read int $serviced_in_a_day
 * @property-read int $serviced_minutes_in_a_day
 * @property-read int $serviced_in_a_week
 * @property-read int $serviced_minutes_in_a_week
 * @property-read int $serviced_in_a_month
 * @property-read int $serviced_minutes_in_a_month
 * @property \Illuminate\Database\Eloquent\Collection<int, Session> $sessions
 */
class Employee extends Model {
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    /** @return HasMany<Session, $this> */
    public function sessions(): HasMany {
        return $this->hasMany(Session::class, 'serviced_by');
    }

    /** @return HasMany<Session, $this> */
    private function activeSessions(): HasMany {
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
