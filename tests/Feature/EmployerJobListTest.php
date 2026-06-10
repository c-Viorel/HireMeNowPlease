<?php

use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function employerWithJobs(): array
{
    $owner = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);
    $company = Company::factory()->for($owner, 'owner')->create(['status' => 'approved']);

    $backend = Job::factory()->for($company)->create(['title' => 'Backend Engineer', 'status' => JobStatus::Published]);
    $designer = Job::factory()->for($company)->create(['title' => 'Product Designer', 'status' => JobStatus::Draft]);

    return ['owner' => $owner, 'company' => $company, 'backend' => $backend, 'designer' => $designer];
}

it('filters employer jobs by title search', function () {
    ['owner' => $owner] = employerWithJobs();

    $this->actingAs($owner)
        ->get(route('employer.jobs.index', ['q' => 'Backend']))
        ->assertOk()
        ->assertSee('Backend Engineer')
        ->assertDontSee('Product Designer');
});

it('filters employer jobs by status', function () {
    ['owner' => $owner] = employerWithJobs();

    $this->actingAs($owner)
        ->get(route('employer.jobs.index', ['status' => JobStatus::Draft->value]))
        ->assertOk()
        ->assertSee('Product Designer')
        ->assertDontSee('Backend Engineer');
});

it('respects the per-page selector on the jobs list', function () {
    ['owner' => $owner, 'company' => $company] = employerWithJobs();
    Job::factory()->count(30)->for($company)->create(['status' => JobStatus::Published]);

    $response = $this->actingAs($owner)
        ->get(route('employer.jobs.index', ['per_page' => 25]))
        ->assertOk();

    expect($response->viewData('jobs')->perPage())->toBe(25);
});

it('exposes status label and color helpers', function () {
    expect(JobStatus::Published->label())->toBe('Publicat')
        ->and(JobStatus::Published->chipClass())->toContain('emerald')
        ->and(JobStatus::Draft->chipClass())->toContain('slate');
});
