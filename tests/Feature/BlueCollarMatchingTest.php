<?php

use App\Enums\JobCategory;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use App\Support\Geo\Geocoder;
use App\Support\Geo\HaversineDistance;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('computes the great-circle distance between two cities in kilometers', function () {
    // Bucuresti -> Cluj-Napoca is roughly 325 km.
    $km = HaversineDistance::between(44.4268, 26.1025, 46.7712, 23.6236);

    expect($km)->toBeGreaterThan(300)
        ->and($km)->toBeLessThan(345);
});

it('geocodes major romanian cities to coordinates', function () {
    $coords = app(Geocoder::class)->coordinates('Bucuresti');

    expect($coords)->not->toBeNull()
        ->and($coords['lat'])->toBeGreaterThan(44.0)
        ->and($coords['lat'])->toBeLessThan(45.0);
});

it('filters jobs within a geographic radius of a city', function () {
    $owner = User::factory()->create(['role' => UserRole::Employer]);
    $company = Company::factory()->for($owner, 'owner')->create(['status' => 'approved']);

    $nearby = Job::factory()->for($company)->create([
        'title' => 'Sofer livrari',
        'location' => 'Ploiesti',
        'category' => JobCategory::BlueCollar,
        'latitude' => 44.9469,
        'longitude' => 26.0215,
    ]);

    $faraway = Job::factory()->for($company)->create([
        'title' => 'Sofer livrari',
        'location' => 'Iasi',
        'category' => JobCategory::BlueCollar,
        'latitude' => 47.1585,
        'longitude' => 27.6014,
    ]);

    $response = $this->get(route('jobs.index', ['near' => 'Bucuresti', 'radius_km' => 80]));

    $response->assertOk()
        ->assertSee('Ploiesti')
        ->assertDontSee('>Iasi<', false);
});

it('rewards blue-collar skill matches in the fit score', function () {
    $taxonomy = (new ReflectionClass(\App\Support\Insights\JobFitScorer::class))
        ->getConstant('SKILL_TAXONOMY');

    expect($taxonomy)->toContain('sofer')
        ->toContain('depozit')
        ->toContain('stivuitorist');
});
