<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Shared\Traits\Authorable;

class Session extends Model {
    use Authorable;

    protected static $authorableType = "staff-session";

    protected $fillable = [
        "instance_id", "schedule_id", "serviced_by", "time", "tariff", "price", "status"
    ];
}
