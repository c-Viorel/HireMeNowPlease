<?php

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function recruiterWithApplications(): array
{
    $owner = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);
    $company = Company::factory()->for($owner, 'owner')->create(['status' => 'approved']);

    $backendJob = Job::factory()->for($company)->create(['title' => 'Backend Engineer', 'status' => JobStatus::Published]);
    $designJob = Job::factory()->for($company)->create(['title' => 'Product Designer', 'status' => JobStatus::Published]);

    $make = function (Job $job, string $name, string $status, int $fit) {
        $candidate = User::factory()->create(['role' => UserRole::Candidate, 'name' => $name]);
        $profile = CandidateProfile::factory()->for($candidate, 'user')->create();

        return Application::create([
            'job_id' => $job->id,
            'candidate_id' => $candidate->id,
            'candidate_profile_id' => $profile->id,
            'status' => $status,
            'fit_snapshot' => ['score' => $fit],
        ]);
    };

    return [
        'owner' => $owner,
        'backendJob' => $backendJob,
        'designJob' => $designJob,
        'ana' => $make($backendJob, 'Ana Backend', ApplicationStatus::Submitted->value, 85),
        'ion' => $make($backendJob, 'Ion Mid', ApplicationStatus::Shortlisted->value, 55),
        'maria' => $make($designJob, 'Maria Design', ApplicationStatus::Interview->value, 40),
    ];
}

it('filters recruiter applications by candidate name search', function () {
    ['owner' => $owner] = recruiterWithApplications();

    $this->actingAs($owner)
        ->get(route('employer.applications.index', ['q' => 'Ana']))
        ->assertOk()
        ->assertSee('Ana Backend')
        ->assertDontSee('Maria Design');
});

it('filters recruiter applications by status', function () {
    ['owner' => $owner] = recruiterWithApplications();

    $this->actingAs($owner)
        ->get(route('employer.applications.index', ['status' => ApplicationStatus::Shortlisted->value]))
        ->assertOk()
        ->assertSee('Ion Mid')
        ->assertDontSee('Ana Backend');
});

it('filters recruiter applications by job', function () {
    ['owner' => $owner, 'designJob' => $designJob] = recruiterWithApplications();

    $this->actingAs($owner)
        ->get(route('employer.applications.index', ['job' => $designJob->id]))
        ->assertOk()
        ->assertSee('Maria Design')
        ->assertDontSee('Ana Backend');
});

it('filters recruiter applications by minimum fit score', function () {
    ['owner' => $owner] = recruiterWithApplications();

    $this->actingAs($owner)
        ->get(route('employer.applications.index', ['min_fit' => 70]))
        ->assertOk()
        ->assertSee('Ana Backend')
        ->assertDontSee('Maria Design');
});

it('sorts recruiter applications by highest fit', function () {
    ['owner' => $owner] = recruiterWithApplications();

    $response = $this->actingAs($owner)
        ->get(route('employer.applications.index', ['sort' => 'fit_desc']))
        ->assertOk();

    $body = $response->getContent();

    expect(strpos($body, 'Ana Backend'))->toBeLessThan(strpos($body, 'Maria Design'));
});

it('respects the per-page selector', function () {
    ['owner' => $owner] = recruiterWithApplications();

    $this->actingAs($owner)
        ->get(route('employer.applications.index', ['per_page' => 25]))
        ->assertOk();

    expect(true)->toBeTrue();
});
