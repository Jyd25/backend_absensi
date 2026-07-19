<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('attendance', function ($user) {
    return true;
});

Broadcast::channel('dashboard', function ($user) {
    return $user->role && $user->role->name === 'Administrator';
});

Broadcast::channel('notification.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('employee', function ($user) {
    return true;
});

Broadcast::channel('history', function ($user) {
    return true;
});
