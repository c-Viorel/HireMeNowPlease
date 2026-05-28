<?php

use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Http\Requests\CompanyRequest;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Job;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('only verified employers can access the employer portal', function () {
    $employer = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);
    $unverifiedEmployer = User::factory()->unverified()->create(['role' => UserRole::Employer]);
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    $admin = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);

    $this->get('/employer/dashboard')->assertRedirect('/login');

    $this->actingAs($employer)->get('/employer/dashboard')->assertOk();
    $this->actingAs($unverifiedEmployer)->get('/employer/dashboard')->assertRedirect('/verify-email');
    $this->actingAs($candidate)->get('/employer/dashboard')->assertForbidden();
    $this->actingAs($admin)->get('/employer/dashboard')->assertForbidden();
});

it('lets an employer create a company with a public logo and publish a job', function () {
    Storage::fake('public');
    $employer = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);

    $this->actingAs($employer)->post('/employer/companies', [
        'name' => 'Acme Recruiting',
        'description' => 'We hire great people.',
        'website' => 'https://example.com',
        'location' => 'Cluj-Napoca',
        'logo' => UploadedFile::fake()->image('logo.png')->size(512),
    ])->assertRedirect('/employer/companies');

    $company = Company::where('name', 'Acme Recruiting')->firstOrFail();

    expect($company->owner_id)->toBe($employer->id)
        ->and($company->slug)->toStartWith('acme-recruiting')
        ->and($company->status)->toBe('pending')
        ->and($company->logo_path)->toStartWith("company-logos/{$company->id}/");

    Storage::disk('public')->assertExists($company->logo_path);

    $this->actingAs($employer)->post('/employer/jobs', [
        'company_id' => $company->id,
        'title' => 'PHP Developer',
        'description' => 'Build Laravel apps.',
        'location' => 'Remote',
        'employment_type' => 'full_time',
        'workplace_type' => 'remote',
        'experience_level' => 'mid',
        'salary_min' => 5000,
        'salary_max' => 9000,
        'status' => 'published',
    ])->assertRedirect('/employer/jobs');

    $this->assertDatabaseHas('jobs', [
        'company_id' => $company->id,
        'title' => 'PHP Developer',
        'status' => 'published',
    ]);

    expect(Job::where('title', 'PHP Developer')->firstOrFail()->published_at)->not->toBeNull();
});

it('prevents an employer from creating a job for a company they do not own', function () {
    $employer = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);
    $otherEmployer = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);
    $otherCompany = Company::factory()->for($otherEmployer, 'owner')->create();

    $this->actingAs($employer)->from('/employer/jobs/create')->post('/employer/jobs', [
        'company_id' => $otherCompany->id,
        'title' => 'PHP Developer',
        'description' => 'Build Laravel apps.',
        'location' => 'Remote',
        'employment_type' => 'full_time',
        'workplace_type' => 'remote',
        'experience_level' => 'mid',
        'status' => 'published',
    ])->assertRedirect('/employer/jobs/create')
        ->assertSessionHasErrors('company_id');

    $this->assertDatabaseMissing('jobs', [
        'company_id' => $otherCompany->id,
        'title' => 'PHP Developer',
    ]);
});

it('rejects invalid company logos and job enum or status values', function () {
    Storage::fake('public');
    $employer = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);
    $company = Company::factory()->for($employer, 'owner')->create();

    $this->actingAs($employer)->from('/employer/companies/create')->post('/employer/companies', [
        'name' => 'Acme Recruiting',
        'website' => 'not-a-url',
        'logo' => UploadedFile::fake()->create('logo.pdf', 100, 'application/pdf'),
    ])->assertRedirect('/employer/companies/create')
        ->assertSessionHasErrors(['website', 'logo']);

    $this->actingAs($employer)->from('/employer/jobs/create')->post('/employer/jobs', [
        'company_id' => $company->id,
        'title' => 'PHP Developer',
        'description' => 'Build Laravel apps.',
        'location' => 'Remote',
        'employment_type' => 'permanent',
        'workplace_type' => 'moon_base',
        'experience_level' => 'mid',
        'salary_min' => 9000,
        'salary_max' => 5000,
        'status' => 'closed',
    ])->assertRedirect('/employer/jobs/create')
        ->assertSessionHasErrors(['employment_type', 'workplace_type', 'salary_max', 'status']);
});

it('rejects svg company logo uploads', function () {
    Storage::fake('public');
    $employer = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);

    $this->actingAs($employer)->from('/employer/companies/create')->post('/employer/companies', [
        'name' => 'Vector Recruiting',
        'logo' => UploadedFile::fake()->createWithContent(
            'logo.svg',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert("x")</script></svg>'
        ),
    ])->assertRedirect('/employer/companies/create')
        ->assertSessionHasErrors('logo');

    $this->assertDatabaseMissing('companies', [
        'name' => 'Vector Recruiting',
    ]);
});

it('restricts company logo validation to raster image formats', function () {
    expect((new CompanyRequest())->rules()['logo'])
        ->toContain('mimes:jpg,jpeg,png,webp');
});

it('creates unique slugs for duplicate company names', function () {
    $employer = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);

    $this->actingAs($employer)->post('/employer/companies', [
        'name' => 'Acme Recruiting',
    ])->assertRedirect('/employer/companies');

    $this->actingAs($employer)->post('/employer/companies', [
        'name' => 'Acme Recruiting',
    ])->assertRedirect('/employer/companies');

    expect(Company::where('name', 'Acme Recruiting')->pluck('slug')->all())
        ->toBe(['acme-recruiting', 'acme-recruiting-2']);
});

it('creates unique slugs for duplicate job titles in the same company', function () {
    $employer = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);
    $company = Company::factory()->for($employer, 'owner')->create();
    $payload = [
        'company_id' => $company->id,
        'title' => 'PHP Developer',
        'description' => 'Build Laravel apps.',
        'location' => 'Remote',
        'employment_type' => 'full_time',
        'workplace_type' => 'remote',
        'experience_level' => 'mid',
        'status' => 'draft',
    ];

    $this->actingAs($employer)->post('/employer/jobs', $payload)->assertRedirect('/employer/jobs');
    $this->actingAs($employer)->post('/employer/jobs', $payload)->assertRedirect('/employer/jobs');

    expect(Job::where('company_id', $company->id)->where('title', 'PHP Developer')->pluck('slug')->all())
        ->toBe(['php-developer', 'php-developer-2']);
});

it('shows employer dashboard company, job, application, and message states', function () {
    $employer = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);

    $this->actingAs($employer)->get('/employer/dashboard')
        ->assertOk()
        ->assertSee('No companies yet')
        ->assertSee('No active jobs yet')
        ->assertSee('No messages yet');

    $company = Company::factory()->for($employer, 'owner')->create([
        'name' => 'Acme Recruiting',
        'status' => 'pending',
    ]);
    $job = Job::factory()->for($company)->create([
        'title' => 'Backend Engineer',
        'status' => JobStatus::Published,
    ]);
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    $profile = CandidateProfile::factory()->for($candidate, 'user')->create();
    $application = Application::create([
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'candidate_profile_id' => $profile->id,
        'message' => 'I am interested in this role.',
    ]);
    $conversation = Conversation::create(['application_id' => $application->id]);
    Message::create([
        'conversation_id' => $conversation->id,
        'sender_id' => $candidate->id,
        'body' => 'Can we talk this week?',
    ]);

    $this->actingAs($employer)->get('/employer/dashboard')
        ->assertOk()
        ->assertSee('Acme Recruiting')
        ->assertSee('Pending')
        ->assertSee('Backend Engineer')
        ->assertSee('1 application')
        ->assertSee('Can we talk this week?');
});
