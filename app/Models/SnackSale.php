<?php

namespace App\Models;

use Database\Factories\SnackSaleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Phobiavr\PhoberLaravelCommon\Traits\Authorable;

/**
 * @property int $id
 * @property string $snack
 * @property int $quantity
 * @property float $price
 * @property int|null $invoice_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read float $total
 * @property Invoice|null $invoice
 */
class SnackSale extends Model {
    use Authorable;
    /** @use HasFactory<SnackSaleFactory> */
    use HasFactory;

    protected $fillable = ['snack', 'quantity', 'price'];

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function getTotalAttribute(): float {
        return $this->price * $this->quantity;
    }
}
