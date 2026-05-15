<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Phobiavr\PhoberLaravelCommon\Traits\Authorable;

class Session extends Model {
    use Authorable;

    protected $table = 'game_sessions';

    protected $fillable = [
        "instance_id", "schedule_id", "serviced_by", "time", "tariff", "price", "status", "started_at"
    ];

    public function servicedBy(): BelongsTo {
        return $this->belongsTo(Employee::class, 'serviced_by');
    }

    public function invoice(): BelongsTo {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function getEndPriceAttribute() {
        return round($this->price * (1 - ($this->discount ?? 0) * 0.1), 2);
    }
}
