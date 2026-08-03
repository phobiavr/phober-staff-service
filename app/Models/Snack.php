<?php

namespace App\Models;

use Database\Factories\SnackFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property int $stock
 * @property float $price
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Snack extends Model {
    /** @use HasFactory<SnackFactory> */
    use HasFactory;

    protected $fillable = ['stock'];
}
