<?php

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'username' => 'testuser',
        'full_name' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('home', absolute: false));
});

test('new users cannot upload profile picture without enough points', function () {
    $response = $this->post(route('register.store'), [
        'username' => 'testuser',
        'full_name' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'profile_picture_url' => 'https://example.com/profile.jpg',
    ]);

    $response->assertSessionHasErrors(['profile_picture_url']);
    $response->assertSessionHasErrors(['profile_picture_url' => 'You need at least 100 points to upload a profile picture.']);
});