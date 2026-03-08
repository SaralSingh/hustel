<?php

use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AdminAuthController;

// ── Admin Auth ─────────────────────────────────────────────────────────────
Route::get('/admin/login',  [AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->middleware('throttle:login')->name('admin.login.post');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// ── Pages ──────────────────────────────────────────────────────────────────
Route::middleware('auth.admin')->group(function () {
    Route::get('/', function () {
        return view('create-room');
    })->name('home');
});

Route::get('/room/{id}',  [RoomController::class, 'roomView'])->name('room.view');
Route::get('/join/{id}',  [RoomController::class, 'waitingView'])->name('room.waiting');

// ── API ────────────────────────────────────────────────────────────────────
Route::middleware(['auth.admin', 'throttle:room-create'])->group(function () {
    Route::post('/rooms', [RoomController::class, 'create']);
    Route::post('/rooms/{id}/end', [RoomController::class, 'endRoom'])->name('room.end');
});

Route::get('/rooms/{id}',       [RoomController::class, 'show'])->name('room.show');

// Chat routes
Route::get('/rooms/{id}/chat',  [RoomController::class, 'fetchMessages'])->name('room.chat.index');
Route::post('/rooms/{id}/chat', [RoomController::class, 'sendMessage'])->name('room.chat.store');

// Proxy & streaming routes
Route::middleware('throttle:proxy')->group(function () {
    Route::get('/proxy-video',      [RoomController::class, 'proxyVideo'])->name('video.proxy');
    Route::get('/clean-playlist',   [RoomController::class, 'cleanPlaylist'])->name('video.clean-playlist');
});
Route::get('/youtube-stream',   [RoomController::class, 'youtubeStream'])->name('video.youtube-stream');
