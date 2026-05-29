<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function fakeOpenAiCvResponse(): void
{
    Http::fake([
        'api.openai.com/v1/responses' => Http::response([
            'output' => [
                [
                    'type' => 'message',
                    'content' => [
                        [
                            'type' => 'output_text',
                            'text' => json_encode([
                                'headline' => 'Senior Laravel Engineer',
                                'summary' => 'Builds marketplace products with Laravel, MySQL and Redis.',
                                'phone' => '+40 721 222 333',
                                'location' => 'Bucuresti',
                                'skills' => ['Laravel', 'PHP', 'MySQL', 'Redis'],
                                'experiences' => [
                                    [
                                        'title' => 'Senior Laravel Engineer',
                                        'company' => 'Product Labs',
                                        'employment_type' => 'full_time',
                                        'location' => 'Remote',
                                        'workplace_type' => 'remote',
                                        'start_date' => '2021-01-01',
                                        'end_date' => null,
                                        'is_current' => true,
                                        'description' => 'Owned APIs and marketplace workflows.',
                                        'skills' => ['Laravel', 'Redis'],
                                    ],
                                ],
                                'educations' => [
                                    [
                                        'institution' => 'Universitatea Bucuresti',
                                        'degree' => 'Licenta',
                                        'field_of_study' => 'Informatica',
                                        'start_date' => '2016-10-01',
                                        'end_date' => '2019-07-01',
                                        'is_current' => false,
                                        'description' => 'Software engineering and databases.',
                                    ],
                                ],
                                'certifications' => [
                                    [
                                        'name' => 'Laravel Certification',
                                        'issuer' => 'Laravel',
                                        'issued_at' => '2023-04-01',
                                        'expires_at' => null,
                                        'credential_url' => 'https://example.com/cert',
                                    ],
                                ],
                                'links' => [
                                    ['label' => 'LinkedIn', 'url' => 'https://linkedin.com/in/demo'],
                                ],
                                'job_preference' => [
                                    'availability' => '30 days',
                                    'experience_level' => 'senior',
                                    'desired_salary_min' => 18000,
                                    'desired_salary_max' => 26000,
                                    'preferred_workplace_types' => ['remote', 'hybrid'],
                                    'preferred_employment_types' => ['full_time'],
                                ],
                                'cv_analysis' => [
                                    'score' => 82,
                                    'strengths' => ['Clear technical positioning'],
                                    'improvements' => ['Add more measurable outcomes'],
                                    'rewrite_suggestions' => ['Quantify marketplace impact'],
                                ],
                            ]),
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);
}

it('lets a candidate preview AI extracted CV profile data before saving', function () {
    Storage::fake('local');
    config()->set('services.openai.key', 'test-key');
    fakeOpenAiCvResponse();

    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    $cv = UploadedFile::fake()->createWithContent(
        'resume.docx',
        docxFixture('Senior Laravel Engineer at Product Labs. Skills: Laravel, PHP, MySQL, Redis.')
    );

    $response = $this->actingAs($candidate)->post(route('candidate.profile.ai.preview'), [
        'cv' => $cv,
    ]);

    $response
        ->assertOk()
        ->assertSee('Senior Laravel Engineer')
        ->assertSee('82%')
        ->assertSee('Add more measurable outcomes');

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer test-key')
        && $request['text']['format']['type'] === 'json_schema');
});

it('saves reviewed AI CV data into the candidate profile only after confirmation', function () {
    Storage::fake('local');
    config()->set('services.openai.key', 'test-key');
    fakeOpenAiCvResponse();

    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    $cv = UploadedFile::fake()->createWithContent(
        'resume.docx',
        docxFixture('Senior Laravel Engineer at Product Labs. Skills: Laravel, PHP, MySQL, Redis.')
    );

    $this->actingAs($candidate)->post(route('candidate.profile.ai.preview'), ['cv' => $cv])
        ->assertOk();

    expect($candidate->fresh()->candidateProfile)->toBeNull();

    $this->actingAs($candidate)->post(route('candidate.profile.ai.apply'))
        ->assertRedirect(route('candidate.profile.edit'));

    $profile = $candidate->fresh()->candidateProfile;

    expect($profile)->not->toBeNull()
        ->and($profile->headline)->toBe('Senior Laravel Engineer')
        ->and($profile->skills)->toBe(['Laravel', 'PHP', 'MySQL', 'Redis'])
        ->and($profile->experiences)->toHaveCount(1)
        ->and($profile->educations)->toHaveCount(1)
        ->and($profile->certifications)->toHaveCount(1)
        ->and($profile->links)->toHaveCount(1)
        ->and($profile->jobPreference->desired_salary_min)->toBe(18000)
        ->and($profile->cv_path)->toStartWith("cvs/{$candidate->id}/");

    Storage::disk('local')->assertExists($profile->cv_path);
});

it('requires a configured OpenAI API key for AI CV import', function () {
    Storage::fake('local');
    config()->set('services.openai.key', null);

    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    $cv = UploadedFile::fake()->createWithContent('resume.docx', docxFixture('Laravel Engineer'));

    $this->actingAs($candidate)
        ->from(route('candidate.profile.ai.create'))
        ->post(route('candidate.profile.ai.preview'), ['cv' => $cv])
        ->assertRedirect(route('candidate.profile.ai.create'))
        ->assertSessionHasErrors('cv');
});

it('skips AI extracted experience rows that do not have a start date', function () {
    Storage::fake('local');
    $candidate = User::factory()->create(['role' => UserRole::Candidate, 'email_verified_at' => now()]);
    $writer = app(\App\Support\Cv\CandidateProfileAiWriter::class);

    $writer->save($candidate, [
        'headline' => 'macOS Developer',
        'summary' => 'Builds endpoint protection products.',
        'phone' => '',
        'location' => 'Bucharest',
        'skills' => ['Swift', 'Objective-C'],
        'experiences' => [
            [
                'title' => 'macOS Developer & Team Lead',
                'company' => 'Heimdal Security',
                'employment_type' => '',
                'location' => 'Bucharest, Romania',
                'workplace_type' => '',
                'start_date' => '',
                'end_date' => '',
                'is_current' => true,
                'description' => 'Led macOS endpoint development.',
                'skills' => ['Swift', 'Objective-C'],
            ],
            [
                'title' => 'iOS Developer',
                'company' => 'Mobile Studio',
                'employment_type' => 'full_time',
                'location' => 'Bucharest',
                'workplace_type' => 'hybrid',
                'start_date' => '2020-01-01',
                'end_date' => '',
                'is_current' => false,
                'description' => 'Built production apps.',
                'skills' => ['Swift'],
            ],
        ],
        'educations' => [],
        'certifications' => [],
        'links' => [],
        'job_preference' => [],
        'cv_analysis' => ['score' => 70, 'strengths' => [], 'improvements' => [], 'rewrite_suggestions' => []],
    ], null, null);

    $profile = $candidate->fresh()->candidateProfile;

    expect($profile->experiences)->toHaveCount(1)
        ->and($profile->experiences->first()->title)->toBe('iOS Developer');
});

function docxFixture(string $text): string
{
    $path = tempnam(sys_get_temp_dir(), 'docx-fixture');
    $zip = new ZipArchive();
    $zip->open($path, ZipArchive::CREATE);
    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="xml" ContentType="application/xml"/></Types>');
    $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>'.htmlspecialchars($text, ENT_XML1).'</w:t></w:r></w:p></w:body></w:document>');
    $zip->close();
    $contents = file_get_contents($path);
    unlink($path);

    return $contents;
}
