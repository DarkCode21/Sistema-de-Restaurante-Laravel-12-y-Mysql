<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('login errors explain whether the email or password is incorrect', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => 'missing@example.com', 'password' => 'password'])
        ->assertInvalid(['email' => 'No existe una cuenta con ese correo.']);

    $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertInvalid(['email' => 'La contraseña no es correcta.']);
});

test('users can stay signed in for 30 days', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        'remember' => true,
    ]);

    $cookie = $response->getCookie(Auth::guard()->getRecallerName(), false);

    expect($cookie)->not->toBeNull()
        ->and($cookie->getExpiresTime())->toBeGreaterThan(now()->addDays(29)->getTimestamp())
        ->toBeLessThan(now()->addDays(31)->getTimestamp());
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
