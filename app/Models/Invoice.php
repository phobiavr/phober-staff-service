<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model {
    protected $fillable = ['customer_id', 'status', 'payment_method', 'customer'];

    public function sessions(): HasMany {
        return $this->hasMany(Session::class);
    }

    public function snackSales(): HasMany {
        return $this->hasMany(SnackSale::class);
    }
}
