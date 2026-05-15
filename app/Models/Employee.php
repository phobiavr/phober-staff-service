<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Phobiavr\PhoberLaravelCommon\Enums\SessionStatusEnum;

class Employee extends Model {
    protected $with = ['sessions'];

    public function sessions() {
        return $this->hasMany(Session::class, 'serviced_by');
    }

    private function activeSessions() {
        return $this->sessions->whereIn('status', [
            SessionStatusEnum::ACTIVE->value,
            SessionStatusEnum::FINISHED->value,
        ]);
    }

    public function getServicedTotalAttribute() {
        return $this->activeSessions()->count();
    }

    public function getServicedMinutesTotalAttribute() {
        return $this->activeSessions()->sum('time');
    }

    public function getServicedInADayAttribute() {
        return $this->activeSessions()
            ->filter(function ($session) {
                return $session->created_at->isToday();
            })
            ->count();
    }

    public function getServicedMinutesInADayAttribute() {
        return $this->activeSessions()
            ->filter(function ($session) {
                return $session->created_at->isToday();
            })
            ->sum('time');
    }

    public function getServicedInAWeekAttribute() {
        return $this->activeSessions()
            ->filter(function ($session) {
                return $session->created_at->between(now()->startOfWeek(), now()->endOfWeek());
            })
            ->count();
    }

    public function getServicedMinutesInAWeekAttribute() {
        return $this->activeSessions()
            ->filter(function ($session) {
                return $session->created_at->between(now()->startOfWeek(), now()->endOfWeek());
            })
            ->sum('time');
    }

    public function getServicedInAMonthAttribute() {
        return $this->activeSessions()
            ->filter(function ($session) {
                return $session->created_at->month == now()->month;
            })
            ->count();
    }

    public function getServicedMinutesInAMonthAttribute() {
        return $this->activeSessions()
            ->filter(function ($session) {
                return $session->created_at->month == now()->month;
            })
            ->sum('time');
    }

    public function getFullNameAttribute(): string {
        return "{$this->first_name} {$this->last_name}";
    }
}
