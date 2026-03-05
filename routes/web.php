<?php

use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;


// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', function () { return view('stream-party'); });
Route::post('/rooms', [RoomController::class, 'create']);
Route::get('/rooms/{id}', [RoomController::class, 'show'])->name('room.show');
