<?php

namespace App\Models;

use Database\Factories\SessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Phobiavr\PhoberLaravelCommon\Traits\Authorable;

/**
 * @property int $id
 * @property int|null $instance_id
 * @property int|null $serviced_by
 * @property int|null $time
 * @property float|null $price
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property float $discount
 * @property int|null $invoice_id
 * @property string|null $note
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read float $end_price
 * @property Employee|null $servicedBy
 * @property Invoice|null $invoice
 */
class Session extends Model {
    use Authorable;
    /** @use HasFactory<SessionFactory> */
    use HasFactory;

    protected $table = 'game_sessions';

    protected $fillable = [
        "instance_id", "serviced_by", "time", "tariff", "price", "status", "started_at"
    ];

    /** @return BelongsTo<Employee, $this> */
    public function servicedBy(): BelongsTo {
        return $this->belongsTo(Employee::class, 'serviced_by');
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function getEndPriceAttribute(): float {
        return round($this->price * (1 - ($this->discount ?? 0) / 100), 2);
    }
}
