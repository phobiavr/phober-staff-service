<?php

namespace App\Enums;

use Carbon\Carbon;

enum PeriodFilterEnum: string {
    case TODAY = 'TODAY';
    case WEEK  = 'WEEK';
    case MONTH = 'MONTH';

    public function startOf(): Carbon {
        return match ($this) {
            self::TODAY => now()->startOfDay(),
            self::WEEK  => now()->startOfWeek(),
            self::MONTH => now()->startOfMonth(),
        };
    }
}
