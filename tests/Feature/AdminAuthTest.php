<?php

use App\Models\Admin;
use App\Models\Room;
use Illuminate\Support\Facades\Hash;
use function Pest\Laravel\{get, post, postJson, actingAs, assertDatabaseHas, assertAuthenticatedAs, assertGuest};

beforeEach(function () {
    // Seed an admin for the tests
    Admin::firstOrCreate(
        ['username' => 'admin'],
        ['password' => Hash::make('hustel@admin2024')]
    );
});

test('login page is accessible', function () {
    get('/admin/login')
        ->assertStatus(200)
        ->assertSee('Admin Login');
});

test('admin can login with correct credentials', function () {
    post('/admin/login', [
        'username' => 'admin',
        'password' => 'hustel@admin2024'
    ])->assertRedirect('/');

    assertAuthenticatedAs(Admin::first(), 'admin');
});

test('admin cannot login with incorrect password', function () {
    post('/admin/login', [
        'username' => 'admin',
        'password' => 'wrongpassword'
    ])->assertSessionHasErrors('username');

    assertGuest('admin');
});

test('unauthenticated user cannot access home', function () {
    get('/')->assertRedirect('/admin/login');
});

test('authenticated admin can access home', function () {
    $admin = Admin::first();
    actingAs($admin, 'admin')->get('/')
        ->assertStatus(200)
        ->assertSee('Create a Stream Room');
});

test('unauthenticated user cannot create room', function () {
    postJson('/rooms', [
        'm3u8_url' => 'http://test.com/video.m3u8'
    ])->assertStatus(401);
});

test('authenticated admin can create room', function () {
    $admin = Admin::first();

    actingAs($admin, 'admin')->postJson('/rooms', [
        'm3u8_url' => 'http://test.com/video.m3u8'
    ])->assertStatus(200)->assertJsonStructure(['room_id', 'access_key']);

    assertDatabaseHas('rooms', [
        'm3u8_url' => 'http://test.com/video.m3u8',
        'is_ended' => 0
    ]);
});

test('admin can end room', function () {
    $admin = Admin::first();

    $room = Room::create([
        'm3u8_url' => 'http://test.com/video.m3u8',
    ]);

    actingAs($admin, 'admin')->postJson("/rooms/{$room->id}/end")
        ->assertStatus(200);

    assertDatabaseHas('rooms', [
        'id' => $room->id,
        'is_ended' => 1
    ]);
});
