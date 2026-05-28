<?php

use App\Enums\UserRole;
use App\Models\User;

test('dashboard redirects users to their role dashboard', function (UserRole $role, string $route) {
    $user = User::factory()->create(['role' => $role, 'email_verified_at' => now()]);

    $this->actingAs($user)->get('/dashboard')
        ->assertRedirect(route($route, absolute: false));
})->with([
    'candidate' => [UserRole::Candidate, 'candidate.dashboard'],
    'employer' => [UserRole::Employer, 'employer.dashboard'],
    'admin' => [UserRole::Admin, 'admin.dashboard'],
]);

test('blocks candidates from employer dashboard', function () {
    $candidate = User::factory()->create([
        'role' => UserRole::Candidate,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($candidate)->get('/employer/dashboard')->assertForbidden();
});

test('blocks employers from candidate dashboard', function () {
    $employer = User::factory()->create([
        'role' => UserRole::Employer,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($employer)->get('/candidate/dashboard')->assertForbidden();
});

test('blocks unauthenticated visitors from role dashboards', function (string $path) {
    $this->get($path)->assertRedirect('/login');
})->with([
    'candidate dashboard' => ['/candidate/dashboard'],
    'employer dashboard' => ['/employer/dashboard'],
    'admin dashboard' => ['/admin/dashboard'],
]);

test('blocks unverified users from protected role dashboards', function () {
    $candidate = User::factory()->unverified()->create(['role' => UserRole::Candidate]);

    $this->actingAs($candidate)->get('/candidate/dashboard')
        ->assertRedirect('/verify-email');
});
