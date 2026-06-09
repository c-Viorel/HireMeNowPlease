<?php

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\EmployerReview;
use App\Models\Job;
use App\Models\User;
use App\Support\Insights\EmployerTrustScore;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function trustCompany(?User $owner = null): Company
{
    $owner ??= User::factory()->create(['role' => UserRole::Employer]);

    return Company::factory()->for($owner, 'owner')->create(['status' => 'approved']);
}

function applicationFor(Company $company, User $candidate): Application
{
    $job = Job::factory()->for($company)->create(['status' => JobStatus::Published]);
    $profile = $candidate->candidateProfile ?? CandidateProfile::factory()->for($candidate, 'user')->create();

    return Application::create([
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'candidate_profile_id' => $profile->id,
        'status' => ApplicationStatus::Submitted,
    ]);
}

it('lets a candidate who applied leave a verified review', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate]);
    $company = trustCompany();
    $application = applicationFor($company, $candidate);

    $this->actingAs($candidate)
        ->post(route('companies.reviews.store', $company), [
            'application_id' => $application->id,
            'rating' => 5,
            'would_apply_again' => true,
            'body' => 'Proces de recrutare clar si rapid.',
        ])
        ->assertRedirect();

    expect(EmployerReview::where('company_id', $company->id)->where('is_verified', true)->count())->toBe(1);
});

it('forbids reviewing a company the candidate never applied to', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate]);
    $company = trustCompany();
    $otherCompany = trustCompany();
    $application = applicationFor($otherCompany, $candidate);

    $this->actingAs($candidate)
        ->post(route('companies.reviews.store', $company), [
            'application_id' => $application->id,
            'rating' => 1,
            'would_apply_again' => false,
            'body' => 'Spam review.',
        ])
        ->assertForbidden();

    expect(EmployerReview::count())->toBe(0);
});

it('combines responsiveness and ratings into a trust score', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate]);
    $company = trustCompany();
    $application = applicationFor($company, $candidate);

    EmployerReview::create([
        'company_id' => $company->id,
        'candidate_id' => $candidate->id,
        'application_id' => $application->id,
        'rating' => 5,
        'would_apply_again' => true,
        'body' => 'Excelent.',
        'is_verified' => true,
        'status' => 'published',
    ]);

    $trust = app(EmployerTrustScore::class)->forCompany($company);

    expect($trust['score'])->toBeGreaterThan(0)
        ->and($trust['review_count'])->toBe(1)
        ->and($trust['average_rating'])->toBe(5.0)
        ->and($trust['label'])->not->toBeEmpty();
});

it('shows the trust score and verified reviews on the public company page', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate]);
    $company = trustCompany();
    $company->update(['name' => 'Encom Recruiting']);
    $application = applicationFor($company, $candidate);

    EmployerReview::create([
        'company_id' => $company->id,
        'candidate_id' => $candidate->id,
        'application_id' => $application->id,
        'rating' => 4,
        'would_apply_again' => true,
        'body' => 'Comunicare foarte buna pe tot parcursul.',
        'is_verified' => true,
        'status' => 'published',
    ]);

    $this->get(route('companies.show', $company))
        ->assertOk()
        ->assertSee('Encom Recruiting')
        ->assertSee('Scor de incredere')
        ->assertSee('Comunicare foarte buna pe tot parcursul.');
});
