# HireMe Recruitment Marketplace Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the first deployable Laravel + MySQL version of HireMe: a recruitment marketplace with candidate and employer accounts, jobs, applications, shortlist, messaging, admin moderation, email notifications, and Hostinger-ready deployment notes.

**Architecture:** Laravel monolith using Blade, Vite, MySQL, first-party auth scaffolding, role-based access, policies, notifications, and local storage for CV/logo uploads. The app is split by feature area so subagents can own vertical slices after the foundation tasks land.

**Tech Stack:** Laravel, PHP, MySQL, Blade, Vite, Tailwind CSS, PHPUnit/Pest-compatible feature tests, Laravel Notifications, SMTP mail, Hostinger Cloud Startup.

---

## Subagent Task Board

Mark each top-level task complete only after its tests pass and its commit exists.

- [x] Task 0: Bootstrap Laravel Application
- [x] Task 1: Domain Schema, Models, Enums, Factories
- [x] Task 2: Authentication, Roles, Email Verification, Access Control
- [x] Task 3: Public Site And Job Board
- [ ] Task 4: Candidate Portal
- [ ] Task 5: Employer Portal
- [ ] Task 6: Applications, Pipeline Statuses, Shortlist
- [ ] Task 7: Messaging
- [ ] Task 8: Admin Panel
- [ ] Task 9: Notifications And Mail
- [ ] Task 10: UI Shell, Responsive Polish, Navigation
- [ ] Task 11: Deployment And Hostinger Readiness
- [ ] Task 12: End-To-End Verification Pass

## Dependency Map

- Task 0 must run first.
- Task 1 depends on Task 0.
- Task 2 depends on Task 1.
- Tasks 3, 4, and 5 depend on Tasks 1 and 2, and can run in parallel.
- Task 6 depends on Tasks 3, 4, and 5.
- Task 7 depends on Task 6.
- Task 8 depends on Tasks 1, 2, 5, and 6.
- Task 9 depends on Tasks 6 and 7.
- Task 10 depends on Tasks 3, 4, 5, 6, 7, and 8.
- Task 11 can start after Task 0, then must be updated after Tasks 9 and 10.
- Task 12 runs last.

## File Structure

The final Laravel project should use these responsibility boundaries:

- `/Users/viorel/Desktop/HireMe/app/Enums`: role, job status, application status, employment type, workplace type enums.
- `/Users/viorel/Desktop/HireMe/app/Models`: Eloquent models for users, profiles, companies, jobs, applications, shortlist entries, conversations, messages.
- `/Users/viorel/Desktop/HireMe/app/Http/Controllers/Public`: homepage, job listing, job detail, company profile pages.
- `/Users/viorel/Desktop/HireMe/app/Http/Controllers/Candidate`: candidate dashboard, profile, CV upload, candidate applications, messages.
- `/Users/viorel/Desktop/HireMe/app/Http/Controllers/Employer`: employer dashboard, companies, jobs, applications, shortlist, messages.
- `/Users/viorel/Desktop/HireMe/app/Http/Controllers/Admin`: admin users, companies, jobs moderation.
- `/Users/viorel/Desktop/HireMe/app/Http/Requests`: form request validation per feature.
- `/Users/viorel/Desktop/HireMe/app/Policies`: ownership and role authorization.
- `/Users/viorel/Desktop/HireMe/app/Notifications`: application, status, and message emails.
- `/Users/viorel/Desktop/HireMe/database/migrations`: normalized schema.
- `/Users/viorel/Desktop/HireMe/database/factories`: factories for tests.
- `/Users/viorel/Desktop/HireMe/database/seeders`: demo data and first admin user.
- `/Users/viorel/Desktop/HireMe/resources/views/public`: homepage and job board.
- `/Users/viorel/Desktop/HireMe/resources/views/candidate`: candidate portal pages.
- `/Users/viorel/Desktop/HireMe/resources/views/employer`: employer portal pages.
- `/Users/viorel/Desktop/HireMe/resources/views/admin`: admin panel pages.
- `/Users/viorel/Desktop/HireMe/resources/views/layouts`: shared app, public, auth, dashboard layouts.
- `/Users/viorel/Desktop/HireMe/tests/Feature`: feature tests by vertical slice.

---

### Task 0: Bootstrap Laravel Application

**Owner:** Foundation subagent  
**Dependencies:** None  
**Files:**
- Create: `/Users/viorel/Desktop/HireMe/composer.json`
- Create: `/Users/viorel/Desktop/HireMe/package.json`
- Create: `/Users/viorel/Desktop/HireMe/.env.example`
- Create: `/Users/viorel/Desktop/HireMe/routes/web.php`
- Create: `/Users/viorel/Desktop/HireMe/resources/views/layouts/app.blade.php`
- Test: `/Users/viorel/Desktop/HireMe/tests/Feature/SmokeTest.php`

- [x] **Step 1: Scaffold Laravel into a temporary directory and merge it into the repo root**

Run:

```bash
cd /Users/viorel/Desktop/HireMe/.worktrees/implementation
composer create-project laravel/laravel .laravel-tmp
rsync -a .laravel-tmp/ ./
rm -rf .laravel-tmp
composer require laravel/breeze --dev
php artisan breeze:install blade --pest
npm install
```

Expected: Laravel files exist in the current directory, Breeze auth views are installed, and dependency installation completes without errors.

- [x] **Step 2: Configure local database for tests**

Modify `/Users/viorel/Desktop/HireMe/phpunit.xml` so the testing database uses SQLite in memory:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

Expected: tests can run without a local MySQL database.

- [x] **Step 3: Add a smoke test**

Create `/Users/viorel/Desktop/HireMe/tests/Feature/SmokeTest.php`:

```php
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('loads the public homepage', function () {
    $response = $this->get('/');

    $response->assertOk();
});
```

- [x] **Step 4: Run bootstrap verification**

Run:

```bash
cd /Users/viorel/Desktop/HireMe/.worktrees/implementation
php artisan test tests/Feature/SmokeTest.php
npm run build
```

Expected: smoke test passes and Vite build completes.

- [x] **Step 5: Commit**

```bash
git add .
git commit -m "chore: bootstrap laravel application"
```

---

### Task 1: Domain Schema, Models, Enums, Factories

**Owner:** Domain foundation subagent  
**Dependencies:** Task 0  
**Files:**
- Create: `/Users/viorel/Desktop/HireMe/app/Enums/UserRole.php`
- Create: `/Users/viorel/Desktop/HireMe/app/Enums/JobStatus.php`
- Create: `/Users/viorel/Desktop/HireMe/app/Enums/ApplicationStatus.php`
- Create: `/Users/viorel/Desktop/HireMe/app/Enums/EmploymentType.php`
- Create: `/Users/viorel/Desktop/HireMe/app/Enums/WorkplaceType.php`
- Modify: `/Users/viorel/Desktop/HireMe/app/Models/User.php`
- Create: `/Users/viorel/Desktop/HireMe/app/Models/CandidateProfile.php`
- Create: `/Users/viorel/Desktop/HireMe/app/Models/Company.php`
- Create: `/Users/viorel/Desktop/HireMe/app/Models/Job.php`
- Create: `/Users/viorel/Desktop/HireMe/app/Models/Application.php`
- Create: `/Users/viorel/Desktop/HireMe/app/Models/Shortlist.php`
- Create: `/Users/viorel/Desktop/HireMe/app/Models/Conversation.php`
- Create: `/Users/viorel/Desktop/HireMe/app/Models/Message.php`
- Create: `/Users/viorel/Desktop/HireMe/database/migrations/*_add_role_and_status_to_users_table.php`
- Create: `/Users/viorel/Desktop/HireMe/database/migrations/*_create_candidate_profiles_table.php`
- Create: `/Users/viorel/Desktop/HireMe/database/migrations/*_create_companies_table.php`
- Create: `/Users/viorel/Desktop/HireMe/database/migrations/*_create_jobs_table.php`
- Create: `/Users/viorel/Desktop/HireMe/database/migrations/*_create_applications_table.php`
- Create: `/Users/viorel/Desktop/HireMe/database/migrations/*_create_shortlists_table.php`
- Create: `/Users/viorel/Desktop/HireMe/database/migrations/*_create_conversations_table.php`
- Create: `/Users/viorel/Desktop/HireMe/database/migrations/*_create_messages_table.php`
- Create: `/Users/viorel/Desktop/HireMe/database/factories/CandidateProfileFactory.php`
- Create: `/Users/viorel/Desktop/HireMe/database/factories/CompanyFactory.php`
- Create: `/Users/viorel/Desktop/HireMe/database/factories/JobFactory.php`
- Create: `/Users/viorel/Desktop/HireMe/tests/Feature/DomainSchemaTest.php`

- [x] **Step 1: Write failing schema relationship tests**

Create `/Users/viorel/Desktop/HireMe/tests/Feature/DomainSchemaTest.php`:

```php
<?php

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Job;
use App\Models\Message;
use App\Models\Shortlist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates the core candidate employer job application graph', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate]);
    $candidateProfile = CandidateProfile::factory()->for($candidate, 'user')->create();
    $employer = User::factory()->create(['role' => UserRole::Employer]);
    $company = Company::factory()->for($employer, 'owner')->create();
    $job = Job::factory()->for($company)->create(['status' => JobStatus::Published]);

    $application = Application::create([
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'candidate_profile_id' => $candidateProfile->id,
        'message' => 'I am interested in this role.',
        'status' => ApplicationStatus::Submitted,
    ]);

    $conversation = Conversation::create(['application_id' => $application->id]);

    Message::create([
        'conversation_id' => $conversation->id,
        'sender_id' => $employer->id,
        'body' => 'Thanks for applying.',
    ]);

    Shortlist::create([
        'company_id' => $company->id,
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
    ]);

    expect($candidate->candidateProfile->is($candidateProfile))->toBeTrue()
        ->and($employer->companies)->toHaveCount(1)
        ->and($company->jobs)->toHaveCount(1)
        ->and($job->applications)->toHaveCount(1)
        ->and($application->conversation->messages)->toHaveCount(1)
        ->and($company->shortlists)->toHaveCount(1);
});
```

- [x] **Step 2: Run test to verify it fails**

Run:

```bash
php artisan test tests/Feature/DomainSchemaTest.php
```

Expected: FAIL because enums/models/migrations do not exist yet.

- [x] **Step 3: Create enums**

Create these enum files:

```php
<?php

namespace App\Enums;

enum UserRole: string
{
    case Candidate = 'candidate';
    case Employer = 'employer';
    case Admin = 'admin';
}
```

```php
<?php

namespace App\Enums;

enum JobStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Published = 'published';
    case Closed = 'closed';
    case Rejected = 'rejected';
}
```

```php
<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Submitted = 'submitted';
    case Viewed = 'viewed';
    case Shortlisted = 'shortlisted';
    case Interview = 'interview';
    case Rejected = 'rejected';
    case Accepted = 'accepted';
}
```

```php
<?php

namespace App\Enums;

enum EmploymentType: string
{
    case FullTime = 'full_time';
    case PartTime = 'part_time';
    case Contract = 'contract';
    case Internship = 'internship';
}
```

```php
<?php

namespace App\Enums;

enum WorkplaceType: string
{
    case Remote = 'remote';
    case Hybrid = 'hybrid';
    case OnSite = 'on_site';
}
```

- [x] **Step 4: Create migrations**

Use Artisan:

```bash
php artisan make:migration add_role_and_status_to_users_table --table=users
php artisan make:model CandidateProfile -mf
php artisan make:model Company -mf
php artisan make:model Job -mf
php artisan make:model Application -m
php artisan make:model Shortlist -m
php artisan make:model Conversation -m
php artisan make:model Message -m
```

Implement migrations with these columns:

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('role')->default('candidate')->after('password');
    $table->boolean('is_active')->default(true)->after('role');
});
```

```php
Schema::create('candidate_profiles', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
    $table->string('phone')->nullable();
    $table->string('location')->nullable();
    $table->string('headline')->nullable();
    $table->text('summary')->nullable();
    $table->json('experience')->nullable();
    $table->json('skills')->nullable();
    $table->string('cv_path')->nullable();
    $table->timestamps();
});
```

```php
Schema::create('companies', function (Blueprint $table) {
    $table->id();
    $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->string('logo_path')->nullable();
    $table->string('website')->nullable();
    $table->string('location')->nullable();
    $table->string('status')->default('pending');
    $table->timestamps();
});
```

```php
Schema::create('jobs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->string('title');
    $table->string('slug');
    $table->text('description');
    $table->string('location')->nullable();
    $table->string('employment_type');
    $table->string('workplace_type');
    $table->string('experience_level')->nullable();
    $table->unsignedInteger('salary_min')->nullable();
    $table->unsignedInteger('salary_max')->nullable();
    $table->string('status')->default('draft');
    $table->timestamp('published_at')->nullable();
    $table->timestamps();
    $table->unique(['company_id', 'slug']);
});
```

```php
Schema::create('applications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('job_id')->constrained()->cascadeOnDelete();
    $table->foreignId('candidate_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('candidate_profile_id')->constrained()->cascadeOnDelete();
    $table->text('message')->nullable();
    $table->string('cv_path')->nullable();
    $table->string('status')->default('submitted');
    $table->timestamps();
    $table->unique(['job_id', 'candidate_id']);
});
```

```php
Schema::create('shortlists', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->foreignId('job_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('candidate_id')->constrained('users')->cascadeOnDelete();
    $table->timestamps();
    $table->unique(['company_id', 'job_id', 'candidate_id']);
});
```

```php
Schema::create('conversations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('application_id')->unique()->constrained()->cascadeOnDelete();
    $table->timestamps();
});
```

```php
Schema::create('messages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
    $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
    $table->text('body');
    $table->timestamp('read_at')->nullable();
    $table->timestamps();
});
```

- [x] **Step 5: Implement model casts and relationships**

Add casts and relationships matching the tests:

```php
protected $casts = [
    'role' => UserRole::class,
    'is_active' => 'boolean',
    'email_verified_at' => 'datetime',
];

public function candidateProfile(): HasOne
{
    return $this->hasOne(CandidateProfile::class);
}

public function companies(): HasMany
{
    return $this->hasMany(Company::class, 'owner_id');
}
```

Use equivalent `belongsTo`, `hasMany`, and `hasOne` relations for all new models.

- [x] **Step 6: Add factories**

Factories must create valid objects with default enum values. Example for `JobFactory`:

```php
public function definition(): array
{
    return [
        'company_id' => Company::factory(),
        'title' => fake()->jobTitle(),
        'slug' => fake()->unique()->slug(),
        'description' => fake()->paragraphs(3, true),
        'location' => fake()->city(),
        'employment_type' => EmploymentType::FullTime,
        'workplace_type' => WorkplaceType::Hybrid,
        'experience_level' => 'mid',
        'status' => JobStatus::Published,
        'published_at' => now(),
    ];
}
```

- [x] **Step 7: Run verification**

Run:

```bash
php artisan test tests/Feature/DomainSchemaTest.php
php artisan test
```

Expected: domain schema test and existing tests pass.

- [x] **Step 8: Commit**

```bash
git add app database tests
git commit -m "feat: add recruitment marketplace domain schema"
```

---

### Task 2: Authentication, Roles, Email Verification, Access Control

**Owner:** Auth subagent  
**Dependencies:** Task 1  
**Files:**
- Modify: `/Users/viorel/Desktop/HireMe/app/Models/User.php`
- Create: `/Users/viorel/Desktop/HireMe/app/Http/Middleware/EnsureUserRole.php`
- Modify: `/Users/viorel/Desktop/HireMe/bootstrap/app.php`
- Modify: `/Users/viorel/Desktop/HireMe/app/Http/Controllers/Auth/RegisteredUserController.php`
- Modify: `/Users/viorel/Desktop/HireMe/resources/views/auth/register.blade.php`
- Modify: `/Users/viorel/Desktop/HireMe/routes/web.php`
- Create: `/Users/viorel/Desktop/HireMe/tests/Feature/Auth/RoleRegistrationTest.php`
- Create: `/Users/viorel/Desktop/HireMe/tests/Feature/Auth/RoleAccessTest.php`

- [x] **Step 1: Write failing role registration tests**

Create tests that assert candidate and employer registration store the selected role:

```php
it('registers a candidate account', function () {
    $this->post('/register', [
        'name' => 'Candidate User',
        'email' => 'candidate@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'candidate',
    ])->assertRedirect('/dashboard');

    $this->assertDatabaseHas('users', [
        'email' => 'candidate@example.com',
        'role' => 'candidate',
    ]);
});

it('registers an employer account', function () {
    $this->post('/register', [
        'name' => 'Employer User',
        'email' => 'employer@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'employer',
    ])->assertRedirect('/dashboard');

    $this->assertDatabaseHas('users', [
        'email' => 'employer@example.com',
        'role' => 'employer',
    ]);
});
```

- [x] **Step 2: Write failing route access tests**

Create tests asserting candidates cannot open employer routes and employers cannot open candidate routes:

```php
it('blocks candidates from employer dashboard', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate]);

    $this->actingAs($candidate)->get('/employer/dashboard')->assertForbidden();
});

it('blocks employers from candidate dashboard', function () {
    $employer = User::factory()->create(['role' => UserRole::Employer]);

    $this->actingAs($employer)->get('/candidate/dashboard')->assertForbidden();
});
```

- [x] **Step 3: Run tests to verify failure**

Run:

```bash
php artisan test tests/Feature/Auth/RoleRegistrationTest.php tests/Feature/Auth/RoleAccessTest.php
```

Expected: FAIL because role input and middleware routes are not wired yet.

- [x] **Step 4: Implement role middleware**

Create `/Users/viorel/Desktop/HireMe/app/Http/Middleware/EnsureUserRole.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        abort_unless($request->user() && in_array($request->user()->role->value, $roles, true), 403);

        return $next($request);
    }
}
```

Register alias `role` in `/Users/viorel/Desktop/HireMe/bootstrap/app.php`.

- [x] **Step 5: Add role selection to registration**

Update validation in `RegisteredUserController`:

```php
'role' => ['required', Rule::in(['candidate', 'employer'])],
```

Store the value:

```php
'role' => UserRole::from($request->role),
```

Add a required select/radio control to `resources/views/auth/register.blade.php` with only `candidate` and `employer`.

- [x] **Step 6: Add role dashboard redirects and protected route groups**

In `/Users/viorel/Desktop/HireMe/routes/web.php`, define:

```php
Route::get('/dashboard', function () {
    return match (auth()->user()->role) {
        \App\Enums\UserRole::Candidate => redirect()->route('candidate.dashboard'),
        \App\Enums\UserRole::Employer => redirect()->route('employer.dashboard'),
        \App\Enums\UserRole::Admin => redirect()->route('admin.dashboard'),
    };
})->middleware(['auth', 'verified'])->name('dashboard');
```

Add minimal dashboard routes with `auth`, `verified`, and `role:*` middleware so access-control tests have concrete endpoints before the full dashboards are built.

- [x] **Step 7: Run verification**

Run:

```bash
php artisan test tests/Feature/Auth
php artisan test
```

Expected: auth role tests and existing tests pass.

- [x] **Step 8: Commit**

```bash
git add app bootstrap resources routes tests
git commit -m "feat: add role based authentication"
```

---

### Task 3: Public Site And Job Board

**Owner:** Public experience subagent  
**Dependencies:** Tasks 1, 2  
**Files:**
- Create: `/Users/viorel/Desktop/HireMe/app/Http/Controllers/Public/HomeController.php`
- Create: `/Users/viorel/Desktop/HireMe/app/Http/Controllers/Public/JobController.php`
- Modify: `/Users/viorel/Desktop/HireMe/routes/web.php`
- Create: `/Users/viorel/Desktop/HireMe/resources/views/public/home.blade.php`
- Create: `/Users/viorel/Desktop/HireMe/resources/views/public/jobs/index.blade.php`
- Create: `/Users/viorel/Desktop/HireMe/resources/views/public/jobs/show.blade.php`
- Test: `/Users/viorel/Desktop/HireMe/tests/Feature/PublicJobBoardTest.php`

- [x] **Step 1: Write failing public job board tests**

```php
it('shows published jobs on the public job board', function () {
    $published = Job::factory()->create(['title' => 'Senior Laravel Developer', 'status' => JobStatus::Published]);
    $draft = Job::factory()->create(['title' => 'Hidden Draft Role', 'status' => JobStatus::Draft]);

    $this->get('/jobs')
        ->assertOk()
        ->assertSee('Senior Laravel Developer')
        ->assertDontSee('Hidden Draft Role');
});

it('filters jobs by workplace type', function () {
    Job::factory()->create(['title' => 'Remote PHP Role', 'workplace_type' => WorkplaceType::Remote, 'status' => JobStatus::Published]);
    Job::factory()->create(['title' => 'Office PHP Role', 'workplace_type' => WorkplaceType::OnSite, 'status' => JobStatus::Published]);

    $this->get('/jobs?workplace_type=remote')
        ->assertOk()
        ->assertSee('Remote PHP Role')
        ->assertDontSee('Office PHP Role');
});
```

- [x] **Step 2: Run tests to verify failure**

Run:

```bash
php artisan test tests/Feature/PublicJobBoardTest.php
```

Expected: FAIL because public controllers/views are missing.

- [x] **Step 3: Implement public controllers**

Create `HomeController::__invoke()` returning featured published jobs. Create `JobController@index()` applying filters for `q`, `location`, `workplace_type`, `employment_type`, and `experience_level`. Create `JobController@show()` loading only published jobs by slug.

- [x] **Step 4: Add routes**

```php
Route::get('/', HomeController::class)->name('home');
Route::get('/jobs', [JobController::class, 'index'])->name('jobs.index');
Route::get('/jobs/{job:slug}', [JobController::class, 'show'])->name('jobs.show');
```

- [x] **Step 5: Build public views**

Views must include:

- homepage with "Caut un job" and "Angajez oameni"
- job cards with company, title, location, workplace type, employment type
- filter form preserving query string
- job detail page with apply CTA hidden behind auth if needed

- [x] **Step 6: Run verification**

Run:

```bash
php artisan test tests/Feature/PublicJobBoardTest.php
php artisan test
```

Expected: public job board tests pass.

- [x] **Step 7: Commit**

```bash
git add app routes resources tests
git commit -m "feat: add public job board"
```

---

### Task 4: Candidate Portal

**Owner:** Candidate portal subagent  
**Dependencies:** Tasks 1, 2  
**Files:**
- Create: `/Users/viorel/Desktop/HireMe/app/Http/Controllers/Candidate/DashboardController.php`
- Create: `/Users/viorel/Desktop/HireMe/app/Http/Controllers/Candidate/ProfileController.php`
- Create: `/Users/viorel/Desktop/HireMe/app/Http/Requests/CandidateProfileRequest.php`
- Create: `/Users/viorel/Desktop/HireMe/resources/views/candidate/dashboard.blade.php`
- Create: `/Users/viorel/Desktop/HireMe/resources/views/candidate/profile/edit.blade.php`
- Modify: `/Users/viorel/Desktop/HireMe/routes/web.php`
- Test: `/Users/viorel/Desktop/HireMe/tests/Feature/CandidatePortalTest.php`

- [ ] **Step 1: Write failing candidate profile tests**

```php
it('lets a candidate update their profile and upload a cv', function () {
    Storage::fake('local');
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);

    $this->actingAs($candidate)->post('/candidate/profile', [
        'phone' => '0712345678',
        'location' => 'Bucuresti',
        'headline' => 'Laravel Developer',
        'summary' => 'I build marketplace products.',
        'skills' => 'PHP,Laravel,MySQL',
        'cv' => UploadedFile::fake()->create('cv.pdf', 256, 'application/pdf'),
    ])->assertRedirect('/candidate/profile');

    $this->assertDatabaseHas('candidate_profiles', [
        'user_id' => $candidate->id,
        'headline' => 'Laravel Developer',
    ]);

    expect($candidate->fresh()->candidateProfile->cv_path)->not->toBeNull();
});
```

- [ ] **Step 2: Run tests to verify failure**

Run:

```bash
php artisan test tests/Feature/CandidatePortalTest.php
```

Expected: FAIL because candidate portal routes/controllers are missing.

- [ ] **Step 3: Implement request validation**

`CandidateProfileRequest` rules:

```php
return [
    'phone' => ['nullable', 'string', 'max:30'],
    'location' => ['nullable', 'string', 'max:120'],
    'headline' => ['nullable', 'string', 'max:160'],
    'summary' => ['nullable', 'string', 'max:3000'],
    'skills' => ['nullable', 'string', 'max:1000'],
    'cv' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
];
```

- [ ] **Step 4: Implement candidate profile controller**

Store CVs under `cvs/{user_id}` on the default disk, convert comma-separated skills to JSON array, and use `updateOrCreate(['user_id' => auth()->id()], [...])`.

- [ ] **Step 5: Add candidate routes**

```php
Route::middleware(['auth', 'verified', 'role:candidate'])->prefix('candidate')->name('candidate.')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
});
```

- [ ] **Step 6: Build views**

Dashboard shows profile completion, recent applications, and recent conversations. Profile view includes the validated fields and current CV link when present.

- [ ] **Step 7: Run verification**

Run:

```bash
php artisan test tests/Feature/CandidatePortalTest.php
php artisan test
```

Expected: candidate portal tests pass.

- [ ] **Step 8: Commit**

```bash
git add app routes resources tests
git commit -m "feat: add candidate portal"
```

---

### Task 5: Employer Portal

**Owner:** Employer portal subagent  
**Dependencies:** Tasks 1, 2  
**Files:**
- Create: `/Users/viorel/Desktop/HireMe/app/Http/Controllers/Employer/DashboardController.php`
- Create: `/Users/viorel/Desktop/HireMe/app/Http/Controllers/Employer/CompanyController.php`
- Create: `/Users/viorel/Desktop/HireMe/app/Http/Controllers/Employer/JobController.php`
- Create: `/Users/viorel/Desktop/HireMe/app/Http/Requests/CompanyRequest.php`
- Create: `/Users/viorel/Desktop/HireMe/app/Http/Requests/JobRequest.php`
- Create: `/Users/viorel/Desktop/HireMe/resources/views/employer/dashboard.blade.php`
- Create: `/Users/viorel/Desktop/HireMe/resources/views/employer/companies/*`
- Create: `/Users/viorel/Desktop/HireMe/resources/views/employer/jobs/*`
- Modify: `/Users/viorel/Desktop/HireMe/routes/web.php`
- Test: `/Users/viorel/Desktop/HireMe/tests/Feature/EmployerPortalTest.php`

- [ ] **Step 1: Write failing employer tests**

```php
it('lets an employer create a company and publish a job', function () {
    Storage::fake('local');
    $employer = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);

    $this->actingAs($employer)->post('/employer/companies', [
        'name' => 'Acme Recruiting',
        'description' => 'We hire great people.',
        'website' => 'https://example.com',
        'location' => 'Cluj-Napoca',
        'logo' => UploadedFile::fake()->image('logo.png'),
    ])->assertRedirect();

    $company = Company::where('name', 'Acme Recruiting')->firstOrFail();

    $this->actingAs($employer)->post('/employer/jobs', [
        'company_id' => $company->id,
        'title' => 'PHP Developer',
        'description' => 'Build Laravel apps.',
        'location' => 'Remote',
        'employment_type' => 'full_time',
        'workplace_type' => 'remote',
        'experience_level' => 'mid',
        'status' => 'published',
    ])->assertRedirect();

    $this->assertDatabaseHas('jobs', [
        'company_id' => $company->id,
        'title' => 'PHP Developer',
        'status' => 'published',
    ]);
});
```

- [ ] **Step 2: Run tests to verify failure**

Run:

```bash
php artisan test tests/Feature/EmployerPortalTest.php
```

Expected: FAIL because employer portal routes/controllers are missing.

- [ ] **Step 3: Implement company and job requests**

Company request validates name, description, website URL, location, and logo image max 2048 KB. Job request validates ownership of `company_id`, title, description, location, employment/workplace enum values, experience level, salary range, and status `draft|published`.

- [ ] **Step 4: Implement employer controllers**

Company creation must set `owner_id` to current user, generate a unique slug, upload logo to `company-logos/{company_id}`, and default company status to `pending`. Job creation must only allow companies owned by the current employer and set `published_at` when status is `published`.

- [ ] **Step 5: Add employer routes**

```php
Route::middleware(['auth', 'verified', 'role:employer'])->prefix('employer')->name('employer.')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::resource('companies', CompanyController::class);
    Route::resource('jobs', JobController::class);
});
```

- [ ] **Step 6: Build views**

Employer dashboard shows company status, active jobs, application counts, and latest messages. Company/job CRUD views should be practical forms with validation errors.

- [ ] **Step 7: Run verification**

Run:

```bash
php artisan test tests/Feature/EmployerPortalTest.php
php artisan test
```

Expected: employer portal tests pass.

- [ ] **Step 8: Commit**

```bash
git add app routes resources tests
git commit -m "feat: add employer portal"
```

---

### Task 6: Applications, Pipeline Statuses, Shortlist

**Owner:** Recruiting workflow subagent  
**Dependencies:** Tasks 3, 4, 5  
**Files:**
- Create: `/Users/viorel/Desktop/HireMe/app/Http/Controllers/Candidate/ApplicationController.php`
- Create: `/Users/viorel/Desktop/HireMe/app/Http/Controllers/Employer/ApplicationController.php`
- Create: `/Users/viorel/Desktop/HireMe/app/Http/Controllers/Employer/ShortlistController.php`
- Create: `/Users/viorel/Desktop/HireMe/app/Http/Requests/ApplicationRequest.php`
- Modify: `/Users/viorel/Desktop/HireMe/routes/web.php`
- Modify: `/Users/viorel/Desktop/HireMe/resources/views/public/jobs/show.blade.php`
- Create: `/Users/viorel/Desktop/HireMe/resources/views/candidate/applications/index.blade.php`
- Create: `/Users/viorel/Desktop/HireMe/resources/views/employer/applications/index.blade.php`
- Create: `/Users/viorel/Desktop/HireMe/resources/views/employer/applications/show.blade.php`
- Test: `/Users/viorel/Desktop/HireMe/tests/Feature/ApplicationWorkflowTest.php`

- [ ] **Step 1: Write failing workflow tests**

```php
it('lets a verified candidate apply once and lets employer update status', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    $profile = CandidateProfile::factory()->for($candidate, 'user')->create(['cv_path' => 'cvs/demo.pdf']);
    $employer = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);
    $company = Company::factory()->for($employer, 'owner')->create();
    $job = Job::factory()->for($company)->create(['status' => JobStatus::Published]);

    $this->actingAs($candidate)->post("/jobs/{$job->slug}/apply", [
        'message' => 'I would like to apply.',
    ])->assertRedirect();

    $application = Application::firstOrFail();

    $this->actingAs($employer)->patch("/employer/applications/{$application->id}/status", [
        'status' => 'shortlisted',
    ])->assertRedirect();

    $this->assertDatabaseHas('applications', [
        'id' => $application->id,
        'status' => 'shortlisted',
    ]);
});
```

- [ ] **Step 2: Run tests to verify failure**

Run:

```bash
php artisan test tests/Feature/ApplicationWorkflowTest.php
```

Expected: FAIL because application routes/controllers are missing.

- [ ] **Step 3: Implement candidate application creation**

Candidates must be verified, have a candidate profile, and cannot apply twice to the same job. Store `candidate_profile_id`, message, current CV path, and initial status `submitted`.

- [ ] **Step 4: Implement employer application management**

Employers may view and update only applications for jobs owned by their companies. Status update accepts `viewed`, `shortlisted`, `interview`, `rejected`, `accepted`.

- [ ] **Step 5: Implement shortlist**

Shortlisting creates or updates `shortlists` and sets the application status to `shortlisted` when the shortlist action comes from an application.

- [ ] **Step 6: Add routes and views**

Add candidate application routes under `/candidate/applications`; employer application routes under `/employer/applications`; add apply form to public job detail page.

- [ ] **Step 7: Run verification**

Run:

```bash
php artisan test tests/Feature/ApplicationWorkflowTest.php
php artisan test
```

Expected: application workflow tests pass.

- [ ] **Step 8: Commit**

```bash
git add app routes resources tests
git commit -m "feat: add application workflow and shortlist"
```

---

### Task 7: Messaging

**Owner:** Messaging subagent  
**Dependencies:** Task 6  
**Files:**
- Create: `/Users/viorel/Desktop/HireMe/app/Http/Controllers/ConversationController.php`
- Create: `/Users/viorel/Desktop/HireMe/app/Http/Controllers/MessageController.php`
- Create: `/Users/viorel/Desktop/HireMe/app/Policies/ConversationPolicy.php`
- Create: `/Users/viorel/Desktop/HireMe/app/Http/Requests/MessageRequest.php`
- Modify: `/Users/viorel/Desktop/HireMe/routes/web.php`
- Create: `/Users/viorel/Desktop/HireMe/resources/views/conversations/index.blade.php`
- Create: `/Users/viorel/Desktop/HireMe/resources/views/conversations/show.blade.php`
- Test: `/Users/viorel/Desktop/HireMe/tests/Feature/MessagingTest.php`

- [ ] **Step 1: Write failing messaging tests**

```php
function createApplicationParticipants(): array
{
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    $profile = CandidateProfile::factory()->for($candidate, 'user')->create(['cv_path' => 'cvs/demo.pdf']);
    $employer = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);
    $company = Company::factory()->for($employer, 'owner')->create();
    $job = Job::factory()->for($company)->create(['status' => JobStatus::Published]);
    $application = Application::create([
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'candidate_profile_id' => $profile->id,
        'message' => 'Interested.',
        'status' => ApplicationStatus::Submitted,
    ]);

    return [$candidate, $employer, $application];
}

it('lets application participants exchange messages', function () {
    [$candidate, $employer, $application] = createApplicationParticipants();

    $this->actingAs($employer)->post("/applications/{$application->id}/conversations", [])
        ->assertRedirect();

    $conversation = Conversation::firstOrFail();

    $this->actingAs($candidate)->post("/conversations/{$conversation->id}/messages", [
        'body' => 'Hello, I am available for an interview.',
    ])->assertRedirect();

    $this->assertDatabaseHas('messages', [
        'conversation_id' => $conversation->id,
        'sender_id' => $candidate->id,
        'body' => 'Hello, I am available for an interview.',
    ]);
});
```

- [ ] **Step 2: Run tests to verify failure**

Run:

```bash
php artisan test tests/Feature/MessagingTest.php
```

Expected: FAIL because messaging controllers/policies are missing.

- [ ] **Step 3: Implement conversation policy**

Allow access only when the current user is the application candidate or owns the application job's company.

- [ ] **Step 4: Implement conversation creation and message sending**

`ConversationController@store` creates one conversation per application using `firstOrCreate`. `MessageController@store` validates `body` as required string max 5000 and stores `sender_id`.

- [ ] **Step 5: Add routes and views**

Routes:

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/applications/{application}/conversations', [ConversationController::class, 'store'])->name('conversations.store');
    Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
    Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store'])->name('messages.store');
});
```

- [ ] **Step 6: Run verification**

Run:

```bash
php artisan test tests/Feature/MessagingTest.php
php artisan test
```

Expected: messaging tests pass.

- [ ] **Step 7: Commit**

```bash
git add app routes resources tests
git commit -m "feat: add application messaging"
```

---

### Task 8: Admin Panel

**Owner:** Admin subagent  
**Dependencies:** Tasks 1, 2, 5, 6  
**Files:**
- Create: `/Users/viorel/Desktop/HireMe/app/Http/Controllers/Admin/DashboardController.php`
- Create: `/Users/viorel/Desktop/HireMe/app/Http/Controllers/Admin/UserController.php`
- Create: `/Users/viorel/Desktop/HireMe/app/Http/Controllers/Admin/CompanyController.php`
- Create: `/Users/viorel/Desktop/HireMe/app/Http/Controllers/Admin/JobController.php`
- Modify: `/Users/viorel/Desktop/HireMe/routes/web.php`
- Create: `/Users/viorel/Desktop/HireMe/resources/views/admin/dashboard.blade.php`
- Create: `/Users/viorel/Desktop/HireMe/resources/views/admin/users/index.blade.php`
- Create: `/Users/viorel/Desktop/HireMe/resources/views/admin/companies/index.blade.php`
- Create: `/Users/viorel/Desktop/HireMe/resources/views/admin/jobs/index.blade.php`
- Test: `/Users/viorel/Desktop/HireMe/tests/Feature/AdminPanelTest.php`

- [ ] **Step 1: Write failing admin tests**

```php
it('lets admins moderate companies and jobs', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
    $company = Company::factory()->create(['status' => 'pending']);
    $job = Job::factory()->for($company)->create(['status' => JobStatus::Pending]);

    $this->actingAs($admin)->patch("/admin/companies/{$company->id}", [
        'status' => 'approved',
    ])->assertRedirect();

    $this->actingAs($admin)->patch("/admin/jobs/{$job->id}", [
        'status' => 'published',
    ])->assertRedirect();

    $this->assertDatabaseHas('companies', ['id' => $company->id, 'status' => 'approved']);
    $this->assertDatabaseHas('jobs', ['id' => $job->id, 'status' => 'published']);
});
```

- [ ] **Step 2: Run tests to verify failure**

Run:

```bash
php artisan test tests/Feature/AdminPanelTest.php
```

Expected: FAIL because admin routes/controllers are missing.

- [ ] **Step 3: Implement admin controllers**

Dashboard shows counts for users, companies, jobs, applications. User controller toggles `is_active`. Company controller updates status `pending|approved|blocked`. Job controller updates status `pending|published|closed|rejected`.

- [ ] **Step 4: Add admin routes**

```php
Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
    Route::patch('/companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
    Route::get('/jobs', [JobController::class, 'index'])->name('jobs.index');
    Route::patch('/jobs/{job}', [JobController::class, 'update'])->name('jobs.update');
});
```

- [ ] **Step 5: Add admin seeder**

Modify `/Users/viorel/Desktop/HireMe/database/seeders/DatabaseSeeder.php`:

```php
User::factory()->create([
    'name' => 'HireMe Admin',
    'email' => 'admin@hireme.local',
    'role' => UserRole::Admin,
    'email_verified_at' => now(),
]);
```

- [ ] **Step 6: Run verification**

Run:

```bash
php artisan test tests/Feature/AdminPanelTest.php
php artisan test
```

Expected: admin tests pass.

- [ ] **Step 7: Commit**

```bash
git add app database routes resources tests
git commit -m "feat: add admin moderation panel"
```

---

### Task 9: Notifications And Mail

**Owner:** Notifications subagent  
**Dependencies:** Tasks 6, 7  
**Files:**
- Create: `/Users/viorel/Desktop/HireMe/app/Notifications/NewApplicationNotification.php`
- Create: `/Users/viorel/Desktop/HireMe/app/Notifications/ApplicationStatusChangedNotification.php`
- Create: `/Users/viorel/Desktop/HireMe/app/Notifications/NewMessageNotification.php`
- Modify: application workflow controllers
- Modify: message controller
- Test: `/Users/viorel/Desktop/HireMe/tests/Feature/NotificationTest.php`

- [ ] **Step 1: Write failing notification tests**

```php
function createCandidateEmployerAndPublishedJob(): array
{
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    CandidateProfile::factory()->for($candidate, 'user')->create(['cv_path' => 'cvs/demo.pdf']);
    $employer = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);
    $company = Company::factory()->for($employer, 'owner')->create();
    $job = Job::factory()->for($company)->create(['status' => JobStatus::Published]);

    return [$candidate, $employer, $job];
}

function createApplicationParticipants(): array
{
    [$candidate, $employer, $job] = createCandidateEmployerAndPublishedJob();
    $profile = $candidate->candidateProfile;
    $application = Application::create([
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'candidate_profile_id' => $profile->id,
        'message' => 'Interested.',
        'status' => ApplicationStatus::Submitted,
    ]);

    return [$candidate, $employer, $application];
}

it('notifies employer when a candidate applies', function () {
    Notification::fake();
    [$candidate, $employer, $job] = createCandidateEmployerAndPublishedJob();

    $this->actingAs($candidate)->post("/jobs/{$job->slug}/apply", [
        'message' => 'Interested.',
    ]);

    Notification::assertSentTo($employer, NewApplicationNotification::class);
});

it('notifies candidate when application status changes', function () {
    Notification::fake();
    [$candidate, $employer, $application] = createApplicationParticipants();

    $this->actingAs($employer)->patch("/employer/applications/{$application->id}/status", [
        'status' => 'interview',
    ]);

    Notification::assertSentTo($candidate, ApplicationStatusChangedNotification::class);
});
```

- [ ] **Step 2: Run tests to verify failure**

Run:

```bash
php artisan test tests/Feature/NotificationTest.php
```

Expected: FAIL because notification classes are missing.

- [ ] **Step 3: Implement notifications**

Each notification should return `['mail', 'database']` from `via()`. Email copy must be concise and include a link to the relevant dashboard/application/conversation route.

- [ ] **Step 4: Trigger notifications**

Send:

- `NewApplicationNotification` to company owner after successful application.
- `ApplicationStatusChangedNotification` to candidate after status change.
- `NewMessageNotification` to the other conversation participant after message creation.

- [ ] **Step 5: Run verification**

Run:

```bash
php artisan test tests/Feature/NotificationTest.php
php artisan test
```

Expected: notification tests pass.

- [ ] **Step 6: Commit**

```bash
git add app tests
git commit -m "feat: add recruitment notifications"
```

---

### Task 10: UI Shell, Responsive Polish, Navigation

**Owner:** UI subagent  
**Dependencies:** Tasks 3, 4, 5, 6, 7, 8  
**Files:**
- Modify: `/Users/viorel/Desktop/HireMe/resources/views/layouts/app.blade.php`
- Create: `/Users/viorel/Desktop/HireMe/resources/views/layouts/public.blade.php`
- Create: `/Users/viorel/Desktop/HireMe/resources/views/layouts/dashboard.blade.php`
- Modify: `/Users/viorel/Desktop/HireMe/resources/css/app.css`
- Modify: public/candidate/employer/admin Blade views
- Test: `/Users/viorel/Desktop/HireMe/tests/Feature/NavigationTest.php`

- [ ] **Step 1: Write failing navigation tests**

```php
it('shows candidate navigation only to candidates', function () {
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);

    $this->actingAs($candidate)->get('/candidate/dashboard')
        ->assertOk()
        ->assertSee('Aplicarile mele')
        ->assertDontSee('Publica job');
});

it('shows employer navigation only to employers', function () {
    $employer = User::factory()->create(['role' => UserRole::Employer, 'email_verified_at' => now()]);

    $this->actingAs($employer)->get('/employer/dashboard')
        ->assertOk()
        ->assertSee('Publica job')
        ->assertDontSee('Aplicarile mele');
});
```

- [ ] **Step 2: Run tests to verify failure**

Run:

```bash
php artisan test tests/Feature/NavigationTest.php
```

Expected: FAIL until layouts and navigation are implemented.

- [ ] **Step 3: Implement shared layouts**

Create public and dashboard layouts with:

- public nav: logo, joburi, companii, login, register
- candidate nav: dashboard, profil, aplicari, mesaje
- employer nav: dashboard, companii, joburi, aplicari, mesaje
- admin nav: dashboard, utilizatori, companii, joburi

- [ ] **Step 4: Apply visual system**

Use Tailwind classes in a restrained professional style: clean typography, high contrast, clear forms, status badges, compact dashboards, responsive tables/cards. Avoid decorative landing-page sections; keep the app action-oriented.

- [ ] **Step 5: Run responsive/browser verification**

Run local server:

```bash
php artisan serve
npm run dev
```

Open and inspect:

- `http://127.0.0.1:8000/`
- `http://127.0.0.1:8000/jobs`
- candidate dashboard
- employer dashboard
- admin dashboard

Expected: no broken layouts on desktop and mobile widths; navigation matches role.

- [ ] **Step 6: Run automated verification**

```bash
php artisan test tests/Feature/NavigationTest.php
npm run build
php artisan test
```

Expected: navigation tests, frontend build, and full test suite pass.

- [ ] **Step 7: Commit**

```bash
git add resources tests
git commit -m "feat: polish marketplace interface"
```

---

### Task 11: Deployment And Hostinger Readiness

**Owner:** Deployment subagent  
**Dependencies:** Task 0 initially; final update after Tasks 9 and 10  
**Status Note:** Initial documentation Steps 1-5 are complete; a final deployment refresh remains after notifications and UI work land.
**Files:**
- Create: `/Users/viorel/Desktop/HireMe/docs/deployment/hostinger-cloud-startup.md`
- Modify: `/Users/viorel/Desktop/HireMe/.env.example`
- Modify: `/Users/viorel/Desktop/HireMe/README.md`
- Test: manual command checklist in docs

- [x] **Step 1: Write deployment documentation**

Create `/Users/viorel/Desktop/HireMe/docs/deployment/hostinger-cloud-startup.md` with:

```markdown
# Hostinger Cloud Startup Deployment

## Required Services

- PHP 8.3+ with the Laravel-required extensions enabled
- MySQL database
- Composer
- Node.js for Vite build
- SMTP mailbox or transactional SMTP credentials
- SSH or Git deployment access

## Production Environment

Set these variables in `.env`:

- `APP_KEY=base64:generate-this-on-the-server`
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://your-domain.example`
- `DB_CONNECTION=mysql`
- `DB_HOST=...`
- `DB_PORT=3306`
- `DB_DATABASE=...`
- `DB_USERNAME=...`
- `DB_PASSWORD=...`
- `MAIL_MAILER=smtp`
- `MAIL_HOST=...`
- `MAIL_PORT=587`
- `MAIL_USERNAME=...`
- `MAIL_PASSWORD=...`
- `MAIL_ENCRYPTION=tls`
- `FILESYSTEM_DISK=local`

Keep `.env` out of version control. Generate `APP_KEY` once during first production setup with `php artisan key:generate --force`; do not regenerate `APP_KEY` on an existing production install unless intentionally rotating keys with a rollback plan.

## Web Root / File Layout

- Configure the domain document root to Laravel's `public/` directory when Hostinger allows it.
- If Hostinger requires `public_html`, keep Laravel application files outside the public web root and make `public_html` serve the contents of Laravel `public/` using Hostinger-supported layout/rewrite.
- Before smoke testing, verify `/.env`, `/composer.json`, and `/storage/logs/laravel.log` are not publicly accessible.

## Release Commands

Use composer2 instead of composer on Hostinger accounts where Composer 1 is the default alias.

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Smoke Checks

- homepage loads
- `/jobs` loads
- registration works
- email verification sends
- CV upload works
- employer can publish a job
- candidate can apply
- message notification sends
```

- [x] **Step 2: Update `.env.example`**

Ensure `.env.example` contains database, mail, filesystem, app URL, and queue-related variables with safe example values.

- [x] **Step 3: Update `README.md`**

Document local setup:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev
php artisan serve
```

- [x] **Step 4: Run docs verification**

Run:

```bash
test -f docs/deployment/hostinger-cloud-startup.md
test -f README.md
rg -n "APP_DEBUG=false|php artisan migrate --force|npm run build" docs/deployment/hostinger-cloud-startup.md
```

Expected: all commands succeed and key deployment instructions are present.

- [x] **Step 5: Commit**

```bash
git add .env.example README.md docs/deployment
git commit -m "docs: add hostinger deployment guide"
```

- [ ] **Step 6: Final deployment refresh after Tasks 9 and 10**

This refresh must update mail, queue, notifications, and UI smoke checks after those tasks land.

---

### Task 12: End-To-End Verification Pass

**Owner:** QA/release subagent  
**Dependencies:** Tasks 0-11  
**Files:**
- Modify only bugfix files required by failed verification
- Create: `/Users/viorel/Desktop/HireMe/docs/release/v1-verification.md`

- [ ] **Step 1: Run full automated suite**

Run:

```bash
cd /Users/viorel/Desktop/HireMe
php artisan test
npm run build
```

Expected: all tests pass and production assets build.

- [ ] **Step 2: Run migrations from scratch**

Run:

```bash
php artisan migrate:fresh --seed
```

Expected: migrations and seeders complete without errors.

- [ ] **Step 3: Run local manual smoke test**

Run:

```bash
php artisan serve
```

Manually verify:

- candidate registration
- employer registration
- admin login with seeded admin
- candidate profile edit and CV upload
- employer company creation
- employer job publishing
- public job search and filters
- candidate application
- employer status update
- shortlist
- conversation and message send
- email notification captured by local mail/log driver

- [ ] **Step 4: Record verification results**

Create `/Users/viorel/Desktop/HireMe/docs/release/v1-verification.md`:

```markdown
# HireMe V1 Verification

Date: 2026-05-28

## Automated Checks

- `php artisan test`: PASS
- `npm run build`: PASS
- `php artisan migrate:fresh --seed`: PASS

## Manual Checks

- Candidate registration: PASS
- Employer registration: PASS
- Admin login: PASS
- Candidate profile and CV upload: PASS
- Company creation: PASS
- Job publishing: PASS
- Public job search and filters: PASS
- Candidate application: PASS
- Employer status update: PASS
- Shortlist: PASS
- Messaging: PASS
- Email notification path: PASS

## Notes

No launch-blocking issues found.
```

- [ ] **Step 5: Commit**

```bash
git add .
git commit -m "test: verify hireme v1 release"
```

---

## Execution Handoff

Recommended execution mode: **Subagent-Driven**.

Execution rules:

- Dispatch one subagent per task after dependencies are satisfied.
- Each subagent must mark only its own task checkbox from `[ ]` to `[x]` after tests pass and commit is created.
- After each subagent returns, run `git status --short` and the task-specific verification command before dispatching the next dependent task.
- Do not dispatch dependent tasks until required upstream commits exist.
- If two subagents touch the same files, sequence them instead of running them in parallel.

Initial dispatch order:

1. Task 0
2. Task 1
3. Task 2
4. Tasks 3, 4, 5 in parallel
5. Task 6
6. Tasks 7 and 8 after their dependencies
7. Task 9
8. Task 10
9. Task 11 final update
10. Task 12
