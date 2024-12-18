<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Phobiavr\PhoberLaravelCommon\Traits\Authorable;

class Session extends Model {
    use Authorable;

    protected $fillable = [
        "instance_id", "schedule_id", "serviced_by", "time", "tariff", "price", "status"
    ];

    public function servicedBy(): BelongsTo {
        return $this->belongsTo(Employee::class, 'serviced_by');
    }

    public function invoice(): BelongsTo {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function getEndPriceAttribute() {
        return $this->price - ($this->discount * 0.1);
    }
}
