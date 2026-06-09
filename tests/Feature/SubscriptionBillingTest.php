<?php

use App\Enums\JobStatus;
use App\Enums\SubscriptionPlan;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Job;
use App\Models\Subscription;
use App\Models\User;
use App\Support\Billing\PlanGate;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('defaults companies without a subscription to the free plan', function () {
    $company = Company::factory()->create(['status' => 'approved']);

    expect(app(PlanGate::class)->planFor($company))->toBe(SubscriptionPlan::Free);
});

it('allows the free plan up to three active published jobs', function () {
    $company = Company::factory()->create(['status' => 'approved']);
    Job::factory()->count(3)->for($company)->create(['status' => JobStatus::Published]);

    $gate = app(PlanGate::class);

    expect($gate->canPublishJob($company))->toBeFalse();
});

it('lets the pro plan publish more active jobs than free', function () {
    $company = Company::factory()->create(['status' => 'approved']);
    Subscription::create([
        'company_id' => $company->id,
        'plan' => SubscriptionPlan::Pro,
        'status' => 'active',
        'started_at' => now(),
    ]);
    Job::factory()->count(3)->for($company)->create(['status' => JobStatus::Published]);

    expect(app(PlanGate::class)->canPublishJob($company))->toBeTrue();
});

it('blocks publishing a fourth job on the free plan from the employer form', function () {
    $owner = User::factory()->create(['role' => UserRole::Employer]);
    $company = Company::factory()->for($owner, 'owner')->create(['status' => 'approved']);
    Job::factory()->count(3)->for($company)->create(['status' => JobStatus::Published]);

    $this->actingAs($owner)
        ->post(route('employer.jobs.store'), [
            'company_id' => $company->id,
            'title' => 'Al patrulea job',
            'description' => 'Descriere suficient de lunga pentru validare.',
            'employment_type' => 'full_time',
            'workplace_type' => 'remote',
            'status' => 'published',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('status');

    expect(Job::where('title', 'Al patrulea job')->count())->toBe(0);
});
