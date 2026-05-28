<?php

use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Enums\WorkplaceType;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;

function publicJobBoardCompany(array $companyAttributes = [], array $ownerAttributes = []): Company
{
    $owner = User::factory()->create([
        'role' => UserRole::Employer,
        'email_verified_at' => now(),
        ...$ownerAttributes,
    ]);

    return Company::factory()
        ->for($owner, 'owner')
        ->create([
            'status' => 'approved',
            ...$companyAttributes,
        ]);
}

it('shows published jobs and hides draft jobs on the public job board', function () {
    $company = publicJobBoardCompany();

    $publishedJob = Job::factory()->create([
        'company_id' => $company->id,
        'title' => 'Senior Laravel Developer',
        'status' => JobStatus::Published,
        'published_at' => now(),
    ]);

    $draftJob = Job::factory()->create([
        'company_id' => $company->id,
        'title' => 'Hidden Draft Role',
        'status' => JobStatus::Draft,
        'published_at' => null,
    ]);

    $response = $this->get(route('jobs.index'));

    $response
        ->assertOk()
        ->assertSeeText($publishedJob->title)
        ->assertDontSeeText($draftJob->title);
});

it('filters published jobs by workplace type', function () {
    $company = publicJobBoardCompany();

    $remoteJob = Job::factory()->create([
        'company_id' => $company->id,
        'title' => 'Remote Product Engineer',
        'workplace_type' => WorkplaceType::Remote,
        'status' => JobStatus::Published,
    ]);

    $onSiteJob = Job::factory()->create([
        'company_id' => $company->id,
        'title' => 'Office Support Engineer',
        'workplace_type' => WorkplaceType::OnSite,
        'status' => JobStatus::Published,
    ]);

    $response = $this->get(route('jobs.index', [
        'workplace_type' => WorkplaceType::Remote->value,
    ]));

    $response
        ->assertOk()
        ->assertSeeText($remoteJob->title)
        ->assertDontSeeText($onSiteJob->title);
});

it('filters published jobs by search query and location', function () {
    $company = publicJobBoardCompany();

    $matchingJob = Job::factory()->create([
        'company_id' => $company->id,
        'title' => 'Frontend Vue Specialist',
        'description' => 'Build polished candidate experiences.',
        'location' => 'Cluj-Napoca',
        'status' => JobStatus::Published,
    ]);

    $wrongLocationJob = Job::factory()->create([
        'company_id' => $company->id,
        'title' => 'Vue Platform Engineer',
        'description' => 'Frontend Vue delivery for marketplace teams.',
        'location' => 'Bucharest',
        'status' => JobStatus::Published,
    ]);

    $wrongQueryJob = Job::factory()->create([
        'company_id' => $company->id,
        'title' => 'Backend PHP Engineer',
        'location' => 'Cluj-Napoca',
        'status' => JobStatus::Published,
    ]);

    $response = $this->get(route('jobs.index', [
        'q' => 'Vue',
        'location' => 'Cluj',
    ]));

    $response
        ->assertOk()
        ->assertSeeText($matchingJob->title)
        ->assertDontSeeText($wrongLocationJob->title)
        ->assertDontSeeText($wrongQueryJob->title);
});

it('shows homepage calls to action and featured published jobs', function () {
    $company = publicJobBoardCompany();

    $featuredJob = Job::factory()->create([
        'company_id' => $company->id,
        'title' => 'Featured Marketplace Role',
        'status' => JobStatus::Published,
        'published_at' => now()->subHour(),
    ]);

    Job::factory()->create([
        'company_id' => $company->id,
        'title' => 'Draft Homepage Role',
        'status' => JobStatus::Draft,
        'published_at' => null,
    ]);

    $response = $this->get('/');

    $response
        ->assertOk()
        ->assertSeeText('Caut un job')
        ->assertSeeText('Angajez oameni')
        ->assertSeeText($featuredJob->title)
        ->assertDontSeeText('Draft Homepage Role');
});

it('shows only published job detail pages by slug', function (JobStatus $status, int $expectedStatus) {
    $company = publicJobBoardCompany();

    $job = Job::factory()->create([
        'company_id' => $company->id,
        'slug' => 'shared-role',
        'title' => 'Public Detail Role '.$status->value,
        'employment_type' => EmploymentType::FullTime,
        'status' => $status,
        'published_at' => $status === JobStatus::Published ? now() : null,
    ]);

    $response = $this->get(route('jobs.show', [$job->company, $job]));

    $response->assertStatus($expectedStatus);

    if ($expectedStatus === 200) {
        $response
            ->assertSeeText($job->title)
            ->assertSeeText('Aplică')
            ->assertSee(route('login'))
            ->assertSee(route('register'));
    }
})->with([
    'published' => [JobStatus::Published, 200],
    'draft' => [JobStatus::Draft, 404],
    'closed' => [JobStatus::Closed, 404],
    'rejected' => [JobStatus::Rejected, 404],
]);

it('hides published jobs from blocked companies on public pages', function () {
    $approvedCompany = publicJobBoardCompany();
    $blockedCompany = publicJobBoardCompany(['status' => 'blocked']);

    $visibleJob = Job::factory()->for($approvedCompany)->create([
        'title' => 'Visible Approved Company Role',
        'status' => JobStatus::Published,
        'published_at' => now(),
    ]);

    $blockedJob = Job::factory()->for($blockedCompany)->create([
        'title' => 'Blocked Company Role',
        'status' => JobStatus::Published,
        'published_at' => now(),
    ]);

    $this->get(route('jobs.index'))
        ->assertOk()
        ->assertSeeText($visibleJob->title)
        ->assertDontSeeText($blockedJob->title);

    $this->get('/')
        ->assertOk()
        ->assertSeeText($visibleJob->title)
        ->assertDontSeeText($blockedJob->title);

    $this->get(route('jobs.show', [$blockedCompany, $blockedJob]))
        ->assertNotFound();
});

it('hides published jobs from inactive employers on public pages', function () {
    $approvedCompany = publicJobBoardCompany();
    $inactiveEmployerCompany = publicJobBoardCompany([], ['is_active' => false]);

    $visibleJob = Job::factory()->for($approvedCompany)->create([
        'title' => 'Visible Active Employer Role',
        'status' => JobStatus::Published,
        'published_at' => now(),
    ]);

    $inactiveEmployerJob = Job::factory()->for($inactiveEmployerCompany)->create([
        'title' => 'Inactive Employer Role',
        'status' => JobStatus::Published,
        'published_at' => now(),
    ]);

    $this->get(route('jobs.index'))
        ->assertOk()
        ->assertSeeText($visibleJob->title)
        ->assertDontSeeText($inactiveEmployerJob->title);

    $this->get('/')
        ->assertOk()
        ->assertSeeText($visibleJob->title)
        ->assertDontSeeText($inactiveEmployerJob->title);

    $this->get(route('jobs.show', [$inactiveEmployerCompany, $inactiveEmployerJob]))
        ->assertNotFound();
});

it('scopes public job detail pages by company slug when job slugs match', function () {
    $firstCompany = publicJobBoardCompany([
        'name' => 'Northwind Labs',
        'slug' => 'northwind-labs',
    ]);

    $secondCompany = publicJobBoardCompany([
        'name' => 'Contoso Talent',
        'slug' => 'contoso-talent',
    ]);

    $firstJob = Job::factory()
        ->for($firstCompany)
        ->create([
            'slug' => 'shared-role',
            'title' => 'Northwind Laravel Engineer',
            'status' => JobStatus::Published,
            'published_at' => now(),
        ]);

    $secondJob = Job::factory()
        ->for($secondCompany)
        ->create([
            'slug' => 'shared-role',
            'title' => 'Contoso Laravel Engineer',
            'status' => JobStatus::Published,
            'published_at' => now(),
        ]);

    $this->get(route('jobs.show', [$firstCompany, $firstJob]))
        ->assertOk()
        ->assertSeeText($firstCompany->name)
        ->assertSeeText($firstJob->title)
        ->assertDontSeeText($secondCompany->name)
        ->assertDontSeeText($secondJob->title);

    $this->get(route('jobs.show', [$secondCompany, $secondJob]))
        ->assertOk()
        ->assertSeeText($secondCompany->name)
        ->assertSeeText($secondJob->title)
        ->assertDontSeeText($firstCompany->name)
        ->assertDontSeeText($firstJob->title);
});
