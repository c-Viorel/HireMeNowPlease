<?php

use App\Enums\UserRole;
use App\Models\User;

test('registers a candidate account', function () {
    $this->post('/register', [
        'name' => 'Candidate User',
        'email' => 'candidate@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => UserRole::Candidate->value,
    ])->assertRedirect('/dashboard');

    $this->assertDatabaseHas('users', [
        'email' => 'candidate@example.com',
        'role' => UserRole::Candidate->value,
    ]);
});

test('registers an employer account', function () {
    $this->post('/register', [
        'name' => 'Employer User',
        'email' => 'employer@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => UserRole::Employer->value,
    ])->assertRedirect('/dashboard');

    $this->assertDatabaseHas('users', [
        'email' => 'employer@example.com',
        'role' => UserRole::Employer->value,
    ]);
});

test('rejects an invalid registration role', function (string $role) {
    $this->from('/register')->post('/register', [
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => $role,
    ])->assertRedirect('/register')
        ->assertSessionHasErrors('role');

    expect(User::where('email', 'admin@example.com')->exists())->toBeFalse();
})->with([
    'admin' => [UserRole::Admin->value],
    'unexpected role' => ['reviewer'],
]);
