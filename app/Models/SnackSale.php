<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Shared\Traits\Authorable;

class SnackSale extends Model {
    use Authorable;

    protected static $authorableType = "staff-snack-sale";

    protected $fillable = ['snack_id', 'quantity'];

    public function snack(): BelongsTo {
        return $this->belongsTo(Snack::class);
    }

    public function invoice(): BelongsTo {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
