<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model {
    protected $fillable = ['customer_id', 'status', 'payment_method'];

    public function sessions(): BelongsToMany {
        return $this->belongsToMany(Session::class, 'invoice_session');
    }

    public function snackSales(): HasMany {
        return $this->hasMany(SnackSale::class);
    }
}
