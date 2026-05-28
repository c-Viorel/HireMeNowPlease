<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows candidate navigation only to candidates', function () {
    $candidate = User::factory()->create([
        'role' => UserRole::Candidate,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($candidate)->get('/candidate/dashboard')
        ->assertOk()
        ->assertSee('Aplicarile mele')
        ->assertDontSee('Publica job');
});

it('shows employer navigation only to employers', function () {
    $employer = User::factory()->create([
        'role' => UserRole::Employer,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($employer)->get('/employer/dashboard')
        ->assertOk()
        ->assertSee('Publica job')
        ->assertDontSee('Aplicarile mele');
});

it('shows admin navigation only to admins', function () {
    $admin = User::factory()->create([
        'role' => UserRole::Admin,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)->get('/admin/dashboard')
        ->assertOk()
        ->assertSee('Utilizatori')
        ->assertSee('Companii')
        ->assertSee('Joburi')
        ->assertDontSee('Aplicarile mele')
        ->assertDontSee('Publica job');
});

it('shows the public marketplace navigation to guests', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('HireMe')
        ->assertSee('Joburi')
        ->assertSee('Companii')
        ->assertSee('Login')
        ->assertSee('Register');
});
