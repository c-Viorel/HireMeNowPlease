<?php

use App\Enums\SalaryType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use App\Support\Salary\RomanianSalaryCalculator;
use App\Support\Salary\SalaryBenchmark;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('converts a gross salary to net using 2026 romanian contributions', function () {
    $breakdown = app(RomanianSalaryCalculator::class)->grossToNet(10000);

    expect($breakdown->gross)->toBe(10000)
        ->and($breakdown->cas)->toBe(2500)
        ->and($breakdown->cass)->toBe(1000)
        ->and($breakdown->incomeTax)->toBe(650)
        ->and($breakdown->net)->toBe(5850);
});

it('converts a net salary back to gross', function () {
    $gross = app(RomanianSalaryCalculator::class)->netToGross(5850);

    // Round-trip is approximate due to rounding; allow a small tolerance.
    expect($gross)->toBeGreaterThanOrEqual(9950)
        ->and($gross)->toBeLessThanOrEqual(10050);
});

it('exposes the employer total cost including work insurance contribution', function () {
    $breakdown = app(RomanianSalaryCalculator::class)->grossToNet(10000);

    expect($breakdown->employerCost)->toBe(10225);
});

it('shows both gross and estimated net on the public job page', function () {
    $owner = User::factory()->create(['role' => UserRole::Employer]);
    $company = Company::factory()->for($owner, 'owner')->create(['status' => 'approved']);
    $job = Job::factory()->for($company)->create([
        'salary_min' => 8000,
        'salary_max' => 12000,
        'salary_type' => SalaryType::Gross,
    ]);

    $this->get(route('jobs.show', [$company, $job]))
        ->assertOk()
        ->assertSee('brut')
        ->assertSee('net estimat');
});

it('builds a market salary benchmark from published jobs for a role and city', function () {
    $owner = User::factory()->create(['role' => UserRole::Employer]);
    $company = Company::factory()->for($owner, 'owner')->create(['status' => 'approved']);

    foreach ([6000, 8000, 12000] as $max) {
        Job::factory()->for($company)->create([
            'title' => 'Laravel Developer',
            'location' => 'Cluj-Napoca',
            'salary_min' => $max - 2000,
            'salary_max' => $max,
            'salary_type' => SalaryType::Gross,
        ]);
    }

    $reference = Job::factory()->for($company)->create([
        'title' => 'Laravel Developer',
        'location' => 'Cluj-Napoca',
        'salary_min' => 7000,
        'salary_max' => 9000,
        'salary_type' => SalaryType::Gross,
    ]);

    $benchmark = app(SalaryBenchmark::class)->forJob($reference);

    expect($benchmark)->not->toBeNull()
        ->and($benchmark['min'])->toBe(4000)
        ->and($benchmark['max'])->toBe(12000)
        ->and($benchmark['sample'])->toBeGreaterThanOrEqual(3);
});
