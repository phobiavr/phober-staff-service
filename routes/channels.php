<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('sessions.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('instances', function ($user) {
    return $user !== null;
});
