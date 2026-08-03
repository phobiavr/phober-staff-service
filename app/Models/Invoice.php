<?php

namespace App\Models;

use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $customer_id
 * @property string|null $customer
 * @property string $status
 * @property array<string, mixed>|null $payment_method
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read float $total
 * @property \Illuminate\Database\Eloquent\Collection<int, Session> $sessions
 * @property \Illuminate\Database\Eloquent\Collection<int, SnackSale> $snackSales
 */
class Invoice extends Model {
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    protected $fillable = ['customer_id', 'status', 'payment_method', 'customer'];

    protected $casts = ['payment_method' => 'array'];

    /** @return HasMany<Session, $this> */
    public function sessions(): HasMany {
        return $this->hasMany(Session::class);
    }

    /** @return HasMany<SnackSale, $this> */
    public function snackSales(): HasMany {
        return $this->hasMany(SnackSale::class);
    }

    public function getTotalAttribute(): float {
        return $this->snackSales->sum('price') + $this->sessions->sum('end_price');
    }
}
