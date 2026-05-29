<?php

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('lets a candidate update their profile and upload a cv', function () {
    Storage::fake('local');
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);

    $this->actingAs($candidate)->post('/candidate/profile', [
        'phone' => '0712345678',
        'location' => 'Bucuresti',
        'headline' => 'Laravel Developer',
        'summary' => 'I build marketplace products.',
        'experience_level' => 'mid',
        'availability' => '30 days',
        'desired_salary_min' => 12000,
        'desired_salary_max' => 18000,
        'preferred_workplace_types' => ['remote', 'hybrid'],
        'preferred_employment_types' => ['full_time', 'contract'],
        'experiences' => [
            [
                'title' => 'Laravel Developer',
                'company' => 'Marketplace Studio',
                'employment_type' => 'full_time',
                'location' => 'Bucuresti',
                'workplace_type' => 'hybrid',
                'start_date' => '2021-03-01',
                'end_date' => '2024-05-01',
                'description' => 'Built marketplace products for SaaS teams.',
                'skills' => 'PHP, Laravel, MySQL',
            ],
            [
                'title' => 'Senior PHP Developer',
                'company' => 'Current Labs',
                'employment_type' => 'contract',
                'location' => 'Remote',
                'workplace_type' => 'remote',
                'start_date' => '2024-06-01',
                'is_current' => '1',
                'description' => 'Owns backend services and integrations.',
                'skills' => 'API design, Redis',
            ],
        ],
        'educations' => [
            [
                'institution' => 'Universitatea Bucuresti',
                'degree' => 'Licenta',
                'field_of_study' => 'Informatica',
                'start_date' => '2016-10-01',
                'end_date' => '2019-07-01',
                'description' => 'Software engineering and databases.',
            ],
        ],
        'certifications' => [
            [
                'name' => 'Laravel Certification',
                'issuer' => 'Laravel',
                'issued_at' => '2023-04-01',
                'credential_url' => 'https://example.com/cert',
            ],
        ],
        'links' => [
            ['label' => 'LinkedIn', 'url' => 'https://linkedin.com/in/demo'],
            ['label' => 'GitHub', 'url' => 'https://github.com/demo'],
        ],
        'skills' => 'PHP, Laravel, , MySQL',
        'cv' => UploadedFile::fake()->create('cv.pdf', 256, 'application/pdf'),
    ])->assertRedirect('/candidate/profile');

    $this->assertDatabaseHas('candidate_profiles', [
        'user_id' => $candidate->id,
        'headline' => 'Laravel Developer',
    ]);

    $profile = $candidate->fresh()->candidateProfile;

    expect(method_exists($profile, 'experiences'))->toBeTrue()
        ->and(method_exists($profile, 'educations'))->toBeTrue()
        ->and(method_exists($profile, 'certifications'))->toBeTrue()
        ->and(method_exists($profile, 'links'))->toBeTrue()
        ->and(method_exists($profile, 'jobPreference'))->toBeTrue();

    expect($profile->skills)->toBe(['PHP', 'Laravel', 'MySQL'])
        ->and($profile->experiences)->toHaveCount(2)
        ->and($profile->experiences->first()->title)->toBe('Laravel Developer')
        ->and($profile->experiences->last()->is_current)->toBeTrue()
        ->and($profile->educations)->toHaveCount(1)
        ->and($profile->certifications)->toHaveCount(1)
        ->and($profile->links)->toHaveCount(2)
        ->and($profile->jobPreference->availability)->toBe('30 days')
        ->and($profile->jobPreference->preferred_workplace_types)->toBe(['remote', 'hybrid'])
        ->and($profile->cv_path)->not->toBeNull()
        ->and($profile->cv_path)->toStartWith("cvs/{$candidate->id}/");

    Storage::disk('local')->assertExists($profile->cv_path);

    $this->actingAs($candidate->fresh())->get('/candidate/dashboard')
        ->assertOk()
        ->assertSee('100%');
});

it('validates structured candidate profile dates', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);

    $this->actingAs($candidate)->from('/candidate/profile')->post('/candidate/profile', [
        'headline' => 'Laravel Developer',
        'experiences' => [
            [
                'title' => 'Developer',
                'company' => 'Acme',
                'start_date' => '2024-05-01',
                'end_date' => '2024-01-01',
            ],
        ],
        'educations' => [
            [
                'institution' => 'Demo University',
                'degree' => 'Licenta',
                'start_date' => '2021-10-01',
                'end_date' => '2020-07-01',
            ],
        ],
    ])->assertRedirect('/candidate/profile')
        ->assertSessionHasErrors(['experiences.0.end_date', 'educations.0.end_date']);
});

it('preserves the current cv when updating without a new upload', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    CandidateProfile::factory()->for($candidate, 'user')->create(['cv_path' => 'cvs/demo.pdf']);

    $this->actingAs($candidate)->post('/candidate/profile', [
        'headline' => 'Senior Laravel Developer',
        'skills' => 'PHP, Laravel',
    ])->assertRedirect('/candidate/profile');

    expect($candidate->fresh()->candidateProfile->cv_path)->toBe('cvs/demo.pdf');
});

it('rejects invalid candidate cv uploads', function () {
    Storage::fake('local');
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);

    $this->actingAs($candidate)->from('/candidate/profile')->post('/candidate/profile', [
        'headline' => 'Laravel Developer',
        'cv' => UploadedFile::fake()->create('cv.exe', 100, 'application/x-msdownload'),
    ])->assertRedirect('/candidate/profile')
        ->assertSessionHasErrors('cv');

    $this->actingAs($candidate)->from('/candidate/profile')->post('/candidate/profile', [
        'headline' => 'Laravel Developer',
        'cv' => UploadedFile::fake()->create('cv.pdf', 6000, 'application/pdf'),
    ])->assertRedirect('/candidate/profile')
        ->assertSessionHasErrors('cv');
});

it('lets a candidate download their own cv', function () {
    Storage::fake('local');
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    Storage::disk('local')->put("cvs/{$candidate->id}/resume.pdf", 'private cv contents');
    CandidateProfile::factory()->for($candidate, 'user')->create([
        'cv_path' => "cvs/{$candidate->id}/resume.pdf",
    ]);

    $this->actingAs($candidate)->get('/candidate/profile/cv')
        ->assertOk()
        ->assertDownload('resume.pdf');
});

it('redirects unauthenticated users from the cv download route', function () {
    $this->get('/candidate/profile/cv')->assertRedirect('/login');
});

it('blocks non candidates from downloading candidate cvs', function () {
    $employer = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);

    $this->actingAs($employer)->get('/candidate/profile/cv')->assertForbidden();
});

it('deletes the old local cv when a candidate replaces it', function () {
    Storage::fake('local');
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    $oldPath = "cvs/{$candidate->id}/old.pdf";

    Storage::disk('local')->put($oldPath, 'old cv contents');
    CandidateProfile::factory()->for($candidate, 'user')->create(['cv_path' => $oldPath]);

    $this->actingAs($candidate)->post('/candidate/profile', [
        'headline' => 'Senior Laravel Developer',
        'skills' => 'PHP, Laravel',
        'cv' => UploadedFile::fake()->create('new.pdf', 128, 'application/pdf'),
    ])->assertRedirect('/candidate/profile');

    $newPath = $candidate->fresh()->candidateProfile->cv_path;

    expect($newPath)->not->toBe($oldPath);

    Storage::disk('local')->assertMissing($oldPath);
    Storage::disk('local')->assertExists($newPath);
});

it('only verified candidates can access candidate dashboard and profile', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    $unverifiedCandidate = User::factory()->unverified()->create(['role' => UserRole::Candidate]);
    $employer = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);
    $admin = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);

    $this->actingAs($candidate)->get('/candidate/dashboard')->assertOk();
    $this->actingAs($candidate)->get('/candidate/profile')->assertOk();

    $this->actingAs($unverifiedCandidate)->get('/candidate/dashboard')->assertRedirect('/verify-email');
    $this->actingAs($unverifiedCandidate)->get('/candidate/profile')->assertRedirect('/verify-email');

    $this->actingAs($employer)->get('/candidate/dashboard')->assertForbidden();
    $this->actingAs($employer)->get('/candidate/profile')->assertForbidden();

    $this->actingAs($admin)->get('/candidate/dashboard')->assertForbidden();
    $this->actingAs($admin)->get('/candidate/profile')->assertForbidden();
});

it('shows candidate dashboard profile completion and recent activity states', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);

    $this->actingAs($candidate)->get('/candidate/dashboard')
        ->assertOk()
        ->assertSee('Profile completion')
        ->assertSee('No applications yet')
        ->assertSee('No conversations yet');

    $profile = CandidateProfile::factory()->for($candidate, 'user')->create();
    $company = Company::factory()->create();
    $job = Job::factory()->for($company)->create([
        'title' => 'Backend Engineer',
        'status' => JobStatus::Published,
    ]);
    $application = Application::create([
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'candidate_profile_id' => $profile->id,
        'message' => 'I am interested in this role.',
        'status' => ApplicationStatus::Submitted,
    ]);
    Conversation::create(['application_id' => $application->id]);

    $this->actingAs($candidate)->get('/candidate/dashboard')
        ->assertOk()
        ->assertSee('Backend Engineer')
        ->assertSee('Submitted')
        ->assertSee('Conversation about Backend Engineer');
});
