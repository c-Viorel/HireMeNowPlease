<?php

use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('only verified admins can access admin routes', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
    $unverifiedAdmin = User::factory()->unverified()->create(['role' => UserRole::Admin]);
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    $employer = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);

    $this->get('/admin/dashboard')->assertRedirect('/login');

    $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
    $this->actingAs($unverifiedAdmin)->get('/admin/dashboard')->assertRedirect('/verify-email');
    $this->actingAs($candidate)->get('/admin/dashboard')->assertForbidden();
    $this->actingAs($employer)->get('/admin/dashboard')->assertForbidden();
});

it('shows admin dashboard counts', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    $profile = CandidateProfile::factory()->for($candidate, 'user')->create();
    $company = Company::factory()->create();
    $job = Job::factory()->for($company)->create();

    Application::create([
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'candidate_profile_id' => $profile->id,
        'message' => 'I am interested in this role.',
    ]);

    $this->actingAs($admin)->get('/admin/dashboard')
        ->assertOk()
        ->assertSee('Users')
        ->assertSee('Companies')
        ->assertSee('Jobs')
        ->assertSee('Applications')
        ->assertSee('3')
        ->assertSee('1');
});

it('lets admins list users and toggle active state except their own account', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
    $candidate = User::factory()->create([
        'name' => 'Ada Candidate',
        'role' => UserRole::Candidate,
        'email_verified_at' => now(),
        'is_active' => true,
    ]);

    $this->actingAs($admin)->get('/admin/users')
        ->assertOk()
        ->assertSee('Ada Candidate')
        ->assertSee('Active');

    $this->actingAs($admin)->patch("/admin/users/{$candidate->id}", [
        'is_active' => false,
    ])->assertRedirect('/admin/users');

    $this->assertDatabaseHas('users', [
        'id' => $candidate->id,
        'is_active' => false,
    ]);

    $this->actingAs($admin)->from('/admin/users')->patch("/admin/users/{$admin->id}", [
        'is_active' => false,
    ])->assertRedirect('/admin/users')
        ->assertSessionHasErrors('user');

    $this->assertDatabaseHas('users', [
        'id' => $admin->id,
        'is_active' => true,
    ]);
});

it('lets admins moderate companies and jobs', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
    $company = Company::factory()->create(['name' => 'Pending Studio', 'status' => 'pending']);
    $job = Job::factory()->for($company)->create([
        'title' => 'Moderated Engineer',
        'status' => JobStatus::Pending,
        'published_at' => null,
    ]);

    $this->actingAs($admin)->get('/admin/companies')
        ->assertOk()
        ->assertSee('Pending Studio');

    $this->actingAs($admin)->get('/admin/jobs')
        ->assertOk()
        ->assertSee('Moderated Engineer');

    $this->actingAs($admin)->patch("/admin/companies/{$company->id}", [
        'status' => 'approved',
    ])->assertRedirect('/admin/companies');

    $this->actingAs($admin)->patch("/admin/jobs/{$job->id}", [
        'status' => 'published',
    ])->assertRedirect('/admin/jobs');

    $this->assertDatabaseHas('companies', ['id' => $company->id, 'status' => 'approved']);
    $this->assertDatabaseHas('jobs', ['id' => $job->id, 'status' => 'published']);
    expect($job->fresh()->published_at)->not->toBeNull();
});

it('validates admin moderation status updates', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
    $company = Company::factory()->create(['status' => 'pending']);
    $job = Job::factory()->for($company)->create(['status' => JobStatus::Pending]);

    $this->actingAs($admin)->from('/admin/companies')->patch("/admin/companies/{$company->id}", [
        'status' => 'archived',
    ])->assertRedirect('/admin/companies')
        ->assertSessionHasErrors('status');

    $this->actingAs($admin)->from('/admin/jobs')->patch("/admin/jobs/{$job->id}", [
        'status' => 'draft',
    ])->assertRedirect('/admin/jobs')
        ->assertSessionHasErrors('status');
});

it('seeds the first verified admin user', function () {
    $this->seed(DatabaseSeeder::class);

    $admin = User::where('email', 'admin@hireme.local')->firstOrFail();

    expect($admin->name)->toBe('HireMe Admin')
        ->and($admin->role)->toBe(UserRole::Admin)
        ->and($admin->email_verified_at)->not->toBeNull();
});
