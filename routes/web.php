<?php

use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

// ── Pages ──────────────────────────────────────────────────────────────────
Route::get('/', function () {
    return view('create-room');
});
Route::get('/room/{id}',  [RoomController::class, 'roomView'])->name('room.view');
Route::get('/join/{id}',  [RoomController::class, 'waitingView'])->name('room.waiting');

// ── API ────────────────────────────────────────────────────────────────────
Route::post('/rooms',           [RoomController::class, 'create']);
Route::get('/rooms/{id}',       [RoomController::class, 'show'])->name('room.show');
Route::get('/proxy-video',      [RoomController::class, 'proxyVideo'])->name('video.proxy');
Route::get('/clean-playlist',   [RoomController::class, 'cleanPlaylist'])->name('video.clean-playlist');
Route::get('/youtube-stream',   [RoomController::class, 'youtubeStream'])->name('video.youtube-stream');
