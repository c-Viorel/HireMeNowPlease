<?php

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function employerWithCompanies(): array
{
    $owner = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);

    $approved = Company::factory()->for($owner, 'owner')->create(['name' => 'Atlas Retail Systems', 'status' => 'approved']);
    $pending = Company::factory()->for($owner, 'owner')->create(['name' => 'BluePeak Health', 'status' => 'pending']);

    return ['owner' => $owner, 'approved' => $approved, 'pending' => $pending];
}

it('filters employer companies by name search', function () {
    ['owner' => $owner] = employerWithCompanies();

    $this->actingAs($owner)
        ->get(route('employer.companies.index', ['q' => 'Atlas']))
        ->assertOk()
        ->assertSee('Atlas Retail Systems')
        ->assertDontSee('BluePeak Health');
});

it('filters employer companies by status', function () {
    ['owner' => $owner] = employerWithCompanies();

    $this->actingAs($owner)
        ->get(route('employer.companies.index', ['status' => 'pending']))
        ->assertOk()
        ->assertSee('BluePeak Health')
        ->assertDontSee('Atlas Retail Systems');
});

it('respects the per-page selector on the companies list', function () {
    ['owner' => $owner] = employerWithCompanies();
    Company::factory()->count(30)->for($owner, 'owner')->create(['status' => 'approved']);

    $response = $this->actingAs($owner)
        ->get(route('employer.companies.index', ['per_page' => 25]))
        ->assertOk();

    expect($response->viewData('companies')->perPage())->toBe(25);
});

it('exposes company status label and chip helpers', function () {
    $approved = Company::factory()->create(['status' => 'approved']);
    $pending = Company::factory()->create(['status' => 'pending']);
    $rejected = Company::factory()->create(['status' => 'rejected']);

    expect($approved->statusLabel())->toBe('Aprobata')
        ->and($approved->statusChipClass())->toContain('emerald')
        ->and($pending->statusLabel())->toBe('In asteptare')
        ->and($pending->statusChipClass())->toContain('amber')
        ->and($rejected->statusChipClass())->toContain('red');
});
