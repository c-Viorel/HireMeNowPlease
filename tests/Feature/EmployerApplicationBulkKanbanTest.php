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
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function recruiterContext(): array
{
    $owner = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);
    $company = Company::factory()->for($owner, 'owner')->create(['status' => 'approved']);
    $job = Job::factory()->for($company)->create(['title' => 'Backend Engineer', 'status' => JobStatus::Published]);

    $make = function (string $name, string $status) use ($job) {
        $candidate = User::factory()->create(['role' => UserRole::Candidate, 'name' => $name]);
        $profile = CandidateProfile::factory()->for($candidate, 'user')->create();

        return Application::create([
            'job_id' => $job->id,
            'candidate_id' => $candidate->id,
            'candidate_profile_id' => $profile->id,
            'status' => $status,
            'fit_snapshot' => ['score' => 60],
        ]);
    };

    return [
        'owner' => $owner,
        'a' => $make('Ana One', ApplicationStatus::Submitted->value),
        'b' => $make('Ion Two', ApplicationStatus::Submitted->value),
        'c' => $make('Maria Three', ApplicationStatus::Viewed->value),
    ];
}

it('bulk updates the status of selected applications', function () {
    Notification::fake();
    ['owner' => $owner, 'a' => $a, 'b' => $b, 'c' => $c] = recruiterContext();

    $this->actingAs($owner)
        ->patch(route('employer.applications.bulk-status'), [
            'application_ids' => [$a->id, $b->id],
            'status' => ApplicationStatus::Shortlisted->value,
        ])
        ->assertRedirect();

    expect($a->fresh()->status)->toBe(ApplicationStatus::Shortlisted)
        ->and($b->fresh()->status)->toBe(ApplicationStatus::Shortlisted)
        ->and($c->fresh()->status)->toBe(ApplicationStatus::Viewed);
});

it('ignores applications that do not belong to the recruiter on bulk update', function () {
    ['owner' => $owner] = recruiterContext();

    $otherOwner = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);
    $otherCompany = Company::factory()->for($otherOwner, 'owner')->create(['status' => 'approved']);
    $otherJob = Job::factory()->for($otherCompany)->create(['status' => JobStatus::Published]);
    $otherCandidate = User::factory()->create(['role' => UserRole::Candidate]);
    $otherProfile = CandidateProfile::factory()->for($otherCandidate, 'user')->create();
    $foreign = Application::create([
        'job_id' => $otherJob->id,
        'candidate_id' => $otherCandidate->id,
        'candidate_profile_id' => $otherProfile->id,
        'status' => ApplicationStatus::Submitted->value,
    ]);

    $this->actingAs($owner)
        ->patch(route('employer.applications.bulk-status'), [
            'application_ids' => [$foreign->id],
            'status' => ApplicationStatus::Rejected->value,
        ])
        ->assertRedirect();

    expect($foreign->fresh()->status)->toBe(ApplicationStatus::Submitted);
});

it('renders the kanban board grouped by status', function () {
    ['owner' => $owner] = recruiterContext();

    $this->actingAs($owner)
        ->get(route('employer.applications.index', ['view' => 'kanban']))
        ->assertOk()
        ->assertSee('Ana One')
        ->assertSee('Maria Three')
        ->assertSee('Shortlisted');
});

it('allows inline single status change from the list', function () {
    Notification::fake();
    ['owner' => $owner, 'a' => $a] = recruiterContext();

    $this->actingAs($owner)
        ->patch(route('employer.applications.status', $a), [
            'status' => ApplicationStatus::Interview->value,
        ])
        ->assertRedirect();

    expect($a->fresh()->status)->toBe(ApplicationStatus::Interview);
});
