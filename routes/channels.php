<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Main room presence channel (for viewer list + video sync)
Broadcast::channel('room.{roomId}', function ($user, $roomId) {
    return ['id' => $user->id, 'name' => $user->name];
});

// Lobby presence channel (for join request / approval flow)
Broadcast::channel('lobby.{roomId}', function ($user, $roomId) {
    return ['id' => $user->id, 'name' => $user->name];
});
