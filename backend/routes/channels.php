<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    Log::info('Broadcasting auth attempt', [
        'user_id' => $user ? $user->id : 'null',
        'channel_id' => $id,
        'match' => $user ? ((int) $user->id === (int) $id) : false,
    ]);
    
    return (int) $user->id === (int) $id;
});
