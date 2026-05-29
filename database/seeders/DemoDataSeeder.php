<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Enums\WorkplaceType;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Job;
use App\Models\Message;
use App\Models\Shortlist;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'demo1234';

    public function run(): void
    {
        $candidate = User::updateOrCreate(
            ['email' => 'candidate@hireme.local'],
            [
                'name' => 'Ana Popescu',
                'password' => self::DEMO_PASSWORD,
                'role' => UserRole::Candidate,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $demoProfile = CandidateProfile::updateOrCreate(
            ['user_id' => $candidate->id],
            [
                'phone' => '+40 721 000 101',
                'location' => 'Bucuresti',
                'headline' => 'Product-minded QA Analyst deschis pentru roluri remote',
                'summary' => 'Candidat demo cu experienta in testare, documentare si colaborare cu echipe de produs.',
                'experience' => [
                    [
                        'title' => 'QA Analyst',
                        'company' => 'Northstar Digital',
                        'years' => 4,
                    ],
                    [
                        'title' => 'Customer Support Specialist',
                        'company' => 'Atlas Support',
                        'years' => 2,
                    ],
                ],
                'skills' => ['QA manual', 'SQL', 'Jira', 'API testing', 'Scrum', 'Customer empathy'],
                'cv_path' => null,
            ]
        );
        $this->syncStructuredProfile($demoProfile, [
            'headline' => 'Product-minded QA Analyst deschis pentru roluri remote',
            'location' => 'Bucuresti',
            'skills' => ['QA manual', 'SQL', 'Jira', 'API testing', 'Scrum', 'Customer empathy'],
            'experience' => [
                ['title' => 'QA Analyst', 'company' => 'Northstar Digital', 'years' => 4],
                ['title' => 'Customer Support Specialist', 'company' => 'Atlas Support', 'years' => 2],
            ],
        ], 0);

        $employer = User::updateOrCreate(
            ['email' => 'hr@hireme.local'],
            [
                'name' => 'Mihai Ionescu',
                'password' => self::DEMO_PASSWORD,
                'role' => UserRole::Employer,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $companies = collect($this->companies())
            ->map(fn (array $company) => Company::updateOrCreate(
                ['slug' => $company['slug']],
                [
                    'owner_id' => $employer->id,
                    'name' => $company['name'],
                    'description' => $company['description'],
                    'logo_path' => null,
                    'website' => $company['website'],
                    'location' => $company['location'],
                    'status' => 'approved',
                ]
            ))
            ->values();

        $contexts = $this->contexts();
        $workplaces = [WorkplaceType::Remote, WorkplaceType::Hybrid, WorkplaceType::OnSite, WorkplaceType::Hybrid, WorkplaceType::Remote];
        $employmentTypes = [EmploymentType::FullTime, EmploymentType::FullTime, EmploymentType::Contract, EmploymentType::FullTime, EmploymentType::PartTime];
        $locations = ['Bucuresti', 'Cluj-Napoca', 'Iasi', 'Timisoara', 'Brasov'];
        $jobIndex = 0;

        foreach ($this->roles() as $roleIndex => $role) {
            foreach ($contexts as $contextIndex => $context) {
                $company = $companies[$jobIndex % $companies->count()];
                $title = $contextIndex === 0
                    ? $role['title']
                    : $role['title'].' - '.$context['title_suffix'];
                $salaryMin = $role['salary_min'] + ($contextIndex * 500) + (($roleIndex % 4) * 300);
                $salaryMax = $salaryMin + $role['salary_spread'];

                Job::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'slug' => Str::slug($title),
                    ],
                    [
                        'title' => $title,
                        'description' => $this->jobDescription($role, $context, $company->name),
                        'location' => $workplaces[$contextIndex] === WorkplaceType::Remote
                            ? 'Remote, Romania'
                            : $locations[$contextIndex],
                        'employment_type' => $employmentTypes[$contextIndex],
                        'workplace_type' => $workplaces[$contextIndex],
                        'experience_level' => $role['level'],
                        'salary_min' => $salaryMin,
                        'salary_max' => $salaryMax,
                        'status' => JobStatus::Published,
                        'published_at' => now()->subDays($jobIndex % 30)->subMinutes($jobIndex),
                    ]
                );

                $jobIndex++;
            }
        }

        $this->seedRecruitmentActivity($candidate, $employer);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function companies(): array
    {
        return [
            ['name' => 'Northstar Digital', 'slug' => 'northstar-digital', 'location' => 'Bucuresti', 'website' => 'https://northstar.example', 'description' => 'Studio de produse digitale pentru platforme B2B, marketplace si automatizari operationale.'],
            ['name' => 'Carpathia Cloud', 'slug' => 'carpathia-cloud', 'location' => 'Cluj-Napoca', 'website' => 'https://carpathia-cloud.example', 'description' => 'Companie cloud-native care livreaza infrastructura, observabilitate si tooling pentru echipe distribuite.'],
            ['name' => 'Mosaic Fintech', 'slug' => 'mosaic-fintech', 'location' => 'Bucuresti', 'website' => 'https://mosaic-fintech.example', 'description' => 'Platforma fintech pentru plati, reconciliere si produse financiare digitale.'],
            ['name' => 'Atlas Retail Systems', 'slug' => 'atlas-retail-systems', 'location' => 'Timisoara', 'website' => 'https://atlas-retail.example', 'description' => 'Furnizor de solutii software pentru retail omnichannel, stocuri si customer engagement.'],
            ['name' => 'BluePeak Health', 'slug' => 'bluepeak-health', 'location' => 'Iasi', 'website' => 'https://bluepeak-health.example', 'description' => 'Healthtech axat pe programari, dosare digitale si comunicare intre clinici si pacienti.'],
            ['name' => 'Vector Labs', 'slug' => 'vector-labs', 'location' => 'Brasov', 'website' => 'https://vector-labs.example', 'description' => 'Echipa de cercetare aplicata in AI, data products si optimizare de procese.'],
            ['name' => 'BridgeWorks Consulting', 'slug' => 'bridgeworks-consulting', 'location' => 'Bucuresti', 'website' => 'https://bridgeworks.example', 'description' => 'Consultanta de transformare digitala pentru companii enterprise si scale-up-uri.'],
            ['name' => 'Cobalt Manufacturing Tech', 'slug' => 'cobalt-manufacturing-tech', 'location' => 'Sibiu', 'website' => 'https://cobalt-mfg.example', 'description' => 'Software industrial pentru productie, mentenanta predictiva si planificare.'],
            ['name' => 'Nexa Mobility', 'slug' => 'nexa-mobility', 'location' => 'Cluj-Napoca', 'website' => 'https://nexa-mobility.example', 'description' => 'Platforma pentru mobilitate urbana, flote electrice si optimizare de rute.'],
            ['name' => 'BrightEdu', 'slug' => 'brightedu', 'location' => 'Iasi', 'website' => 'https://brightedu.example', 'description' => 'Edtech pentru cursuri online, evaluari si managementul comunitatilor de invatare.'],
            ['name' => 'Harbor Logistics', 'slug' => 'harbor-logistics', 'location' => 'Constanta', 'website' => 'https://harbor-logistics.example', 'description' => 'Companie de logistica digitala pentru transport, depozitare si urmarirea livrarilor.'],
            ['name' => 'Signal CX', 'slug' => 'signal-cx', 'location' => 'Bucuresti', 'website' => 'https://signal-cx.example', 'description' => 'SaaS pentru customer experience, ticketing, analytics si automatizari de suport.'],
            ['name' => 'GreenGrid Energy', 'slug' => 'greengrid-energy', 'location' => 'Oradea', 'website' => 'https://greengrid-energy.example', 'description' => 'Platforma de energie regenerabila pentru monitorizare, raportare si predictii de consum.'],
            ['name' => 'Craft Commerce', 'slug' => 'craft-commerce', 'location' => 'Cluj-Napoca', 'website' => 'https://craft-commerce.example', 'description' => 'Agentie de e-commerce si growth pentru branduri locale si regionale.'],
            ['name' => 'Astra Security', 'slug' => 'astra-security', 'location' => 'Bucuresti', 'website' => 'https://astra-security.example', 'description' => 'Companie de cyber security pentru audit, monitorizare si raspuns la incidente.'],
        ];
    }

    private function seedRecruitmentActivity(User $demoCandidate, User $demoEmployer): void
    {
        $candidateProfile = $demoCandidate->candidateProfile()->firstOrFail();
        $jobs = Job::query()
            ->whereHas('company', fn ($query) => $query->where('owner_id', $demoEmployer->id))
            ->with('company')
            ->orderBy('id')
            ->get();

        $demoCandidateScenarios = [
            [0, ApplicationStatus::Interview, 'Rolul se potriveste foarte bine cu experienta mea in QA, API testing si colaborare cu echipe de produs.', true],
            [1, ApplicationStatus::Shortlisted, 'Sunt interesata de zona de marketplace si pot contribui rapid la claritatea proceselor de testare.', true],
            [2, ApplicationStatus::Viewed, 'Am lucrat cu fluxuri operationale similare si mi-ar placea sa discutam despre nevoile echipei.', true],
            [3, ApplicationStatus::Submitted, 'Aplic pentru ca produsul si modul de lucru remote se potrivesc cu obiectivele mele profesionale.', false],
            [4, ApplicationStatus::Rejected, 'Multumesc pentru oportunitate. Sunt deschisa la feedback si roluri viitoare potrivite profilului meu.', true],
            [5, ApplicationStatus::Accepted, 'Sunt incantata de rol si disponibila pentru pasii urmatori ai procesului.', true],
            [6, ApplicationStatus::Interview, 'Experienta mea in SQL, Jira si suport pentru clienti poate ajuta echipa sa livreze mai previzibil.', true],
            [7, ApplicationStatus::Viewed, 'Mi-ar placea sa aflu mai multe despre produs, echipa si obiectivele pentru urmatoarele trimestre.', false],
            [8, ApplicationStatus::Submitted, 'Aplic cu interes pentru acest rol si pot trimite detalii suplimentare despre proiectele relevante.', false],
            [9, ApplicationStatus::Shortlisted, 'Cred ca profilul meu combina bine atentia la detaliu cu intelegerea experientei candidatilor.', true],
        ];

        foreach ($demoCandidateScenarios as $scenarioIndex => [$jobOffset, $status, $message, $withConversation]) {
            $application = $this->upsertApplication(
                $jobs[$jobOffset],
                $demoCandidate,
                $candidateProfile,
                $status,
                $message,
                $scenarioIndex
            );

            if ($withConversation) {
                $this->seedConversation($application, $demoCandidate, $demoEmployer, $scenarioIndex);
            }

            if (in_array($status, [ApplicationStatus::Shortlisted, ApplicationStatus::Interview, ApplicationStatus::Accepted], true)) {
                $this->upsertShortlist($application);
            }
        }

        foreach ($this->candidatePersonas() as $candidateIndex => $persona) {
            $candidate = $this->upsertCandidatePersona($persona, $candidateIndex);
            $profile = $candidate->candidateProfile()->firstOrFail();

            for ($applicationIndex = 0; $applicationIndex < 3; $applicationIndex++) {
                $job = $jobs[10 + ($candidateIndex * 3) + $applicationIndex];
                $status = $this->statusFor($candidateIndex, $applicationIndex);
                $application = $this->upsertApplication(
                    $job,
                    $candidate,
                    $profile,
                    $status,
                    $this->applicationMessage($persona, $job, $applicationIndex),
                    20 + ($candidateIndex * 3) + $applicationIndex
                );

                if (($candidateIndex + $applicationIndex) % 2 === 0 || $status === ApplicationStatus::Interview) {
                    $this->seedConversation($application, $candidate, $demoEmployer, 20 + ($candidateIndex * 3) + $applicationIndex);
                }

                if (in_array($status, [ApplicationStatus::Shortlisted, ApplicationStatus::Interview, ApplicationStatus::Accepted], true)) {
                    $this->upsertShortlist($application);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $persona
     */
    private function upsertCandidatePersona(array $persona, int $index): User
    {
        $candidate = User::updateOrCreate(
            ['email' => $persona['email']],
            [
                'name' => $persona['name'],
                'password' => self::DEMO_PASSWORD,
                'role' => UserRole::Candidate,
                'email_verified_at' => now()->subDays(30 - $index),
                'is_active' => true,
            ]
        );

        $profile = CandidateProfile::updateOrCreate(
            ['user_id' => $candidate->id],
            [
                'phone' => '+40 72'.($index + 2).' 000 '.str_pad((string) ($index + 11), 3, '0', STR_PAD_LEFT),
                'location' => $persona['location'],
                'headline' => $persona['headline'],
                'summary' => $persona['summary'],
                'experience' => $persona['experience'],
                'skills' => $persona['skills'],
                'cv_path' => null,
            ]
        );
        $this->syncStructuredProfile($profile, $persona, $index + 1);

        return $candidate;
    }

    private function upsertApplication(
        Job $job,
        User $candidate,
        CandidateProfile $profile,
        ApplicationStatus $status,
        string $message,
        int $ageIndex
    ): Application {
        return Application::updateOrCreate(
            [
                'job_id' => $job->id,
                'candidate_id' => $candidate->id,
            ],
            [
                'candidate_profile_id' => $profile->id,
                'message' => $message,
                'cv_path' => null,
                'profile_snapshot' => $profile->fresh()->snapshot(),
                'status' => $status,
                'created_at' => now()->subDays(18 - ($ageIndex % 18))->subHours($ageIndex % 9),
                'updated_at' => now()->subDays(12 - ($ageIndex % 12))->subMinutes($ageIndex * 3),
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $persona
     */
    private function syncStructuredProfile(CandidateProfile $profile, array $persona, int $index): void
    {
        $profile->experiences()->delete();
        $profile->educations()->delete();
        $profile->certifications()->delete();
        $profile->links()->delete();

        foreach ($persona['experience'] as $experienceIndex => $experience) {
            $startYear = 2024 - (int) ($experience['years'] ?? 3) - $experienceIndex;

            $profile->experiences()->create([
                'title' => $experience['title'],
                'company' => $experience['company'],
                'employment_type' => $experienceIndex === 0 ? 'full_time' : 'contract',
                'location' => $persona['location'] ?? 'Remote, Romania',
                'workplace_type' => str_contains((string) ($persona['location'] ?? ''), 'Remote') ? 'remote' : ($experienceIndex === 0 ? 'hybrid' : 'remote'),
                'start_date' => "{$startYear}-03-01",
                'end_date' => $experienceIndex === 0 ? null : ($startYear + (int) ($experience['years'] ?? 2)).'-02-01',
                'is_current' => $experienceIndex === 0,
                'description' => $this->profileExperienceDescription($persona, $experience),
                'skills' => array_slice($persona['skills'], 0, 4),
                'sort_order' => $experienceIndex,
            ]);
        }

        $profile->educations()->create([
            'institution' => ['Universitatea Bucuresti', 'Universitatea Babes-Bolyai', 'Universitatea Alexandru Ioan Cuza', 'Politehnica Timisoara'][$index % 4],
            'degree' => $index % 3 === 0 ? 'Master' : 'Licenta',
            'field_of_study' => $index % 2 === 0 ? 'Informatica economica' : 'Computer Science',
            'start_date' => '2015-10-01',
            'end_date' => '2018-07-01',
            'is_current' => false,
            'description' => 'Proiecte aplicate, baze de date, analiza de produs si lucru in echipe multidisciplinare.',
            'sort_order' => 0,
        ]);

        $profile->certifications()->create([
            'name' => $index % 2 === 0 ? 'Agile Product Delivery' : 'Professional Skills Certificate',
            'issuer' => $index % 2 === 0 ? 'Scrum.org' : 'LinkedIn Learning',
            'issued_at' => '2023-05-01',
            'expires_at' => null,
            'credential_url' => 'https://example.com/credentials/'.Str::slug((string) ($persona['headline'] ?? 'candidate')),
            'sort_order' => 0,
        ]);

        $profile->links()->create([
            'label' => 'LinkedIn',
            'url' => 'https://linkedin.com/in/'.Str::slug((string) ($persona['name'] ?? 'ana-popescu')),
            'sort_order' => 0,
        ]);

        if (str_contains(implode(' ', $persona['skills']), 'Laravel') || str_contains(implode(' ', $persona['skills']), 'Python')) {
            $profile->links()->create([
                'label' => 'Portfolio',
                'url' => 'https://portfolio.example/'.Str::slug((string) ($persona['name'] ?? 'candidate')),
                'sort_order' => 1,
            ]);
        }

        $profile->jobPreference()->updateOrCreate(
            [],
            [
                'availability' => $index % 3 === 0 ? 'Immediate' : '30 days',
                'experience_level' => $index % 4 === 0 ? 'senior' : 'mid',
                'desired_salary_min' => 9000 + ($index * 700),
                'desired_salary_max' => 14000 + ($index * 900),
                'preferred_workplace_types' => $index % 2 === 0 ? ['remote', 'hybrid'] : ['hybrid', 'on_site'],
                'preferred_employment_types' => ['full_time', 'contract'],
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $persona
     * @param  array<string, mixed>  $experience
     */
    private function profileExperienceDescription(array $persona, array $experience): string
    {
        $skills = implode(', ', array_slice($persona['skills'], 0, 3));

        return "A lucrat ca {$experience['title']} cu focus pe {$skills}. A contribuit la initiative masurabile, colaborare cross-functional si documentarea deciziilor importante.";
    }

    private function seedConversation(Application $application, User $candidate, User $employer, int $threadIndex): void
    {
        $conversation = Conversation::firstOrCreate(
            ['application_id' => $application->id],
            [
                'created_at' => $application->created_at->copy()->addHours(4),
                'updated_at' => $application->updated_at,
            ]
        );

        $messages = [
            [
                'sender_id' => $candidate->id,
                'body' => 'Buna ziua, multumesc pentru confirmarea candidaturii. Pot detalia proiectele relevante si disponibilitatea pentru interviu.',
                'created_at' => $application->created_at->copy()->addHours(5),
            ],
            [
                'sender_id' => $employer->id,
                'body' => 'Buna, profilul tau este interesant pentru rol. Ne poti spune ce tip de echipa si ritm de lucru cauti in perioada urmatoare?',
                'created_at' => $application->created_at->copy()->addHours(11),
            ],
            [
                'sender_id' => $candidate->id,
                'body' => 'Caut o echipa cu ownership clar, feedback rapid si obiective masurabile. Sunt confortabil(a) cu lucru hibrid sau remote.',
                'created_at' => $application->created_at->copy()->addDay(),
            ],
        ];

        if ($threadIndex % 3 === 0) {
            $messages[] = [
                'sender_id' => $employer->id,
                'body' => 'Perfect. Am putea programa o discutie de 30 de minute saptamana aceasta pentru a trece prin experienta ta recenta.',
                'created_at' => $application->created_at->copy()->addDays(2),
            ];
        }

        foreach ($messages as $message) {
            Message::updateOrCreate(
                [
                    'conversation_id' => $conversation->id,
                    'sender_id' => $message['sender_id'],
                    'body' => $message['body'],
                ],
                [
                    'read_at' => $message['sender_id'] === $candidate->id ? now()->subDay() : null,
                    'created_at' => $message['created_at'],
                    'updated_at' => $message['created_at'],
                ]
            );
        }
    }

    private function upsertShortlist(Application $application): void
    {
        Shortlist::updateOrCreate(
            [
                'company_id' => $application->job->company_id,
                'job_id' => $application->job_id,
                'candidate_id' => $application->candidate_id,
            ],
            [
                'created_at' => $application->updated_at,
                'updated_at' => $application->updated_at,
            ]
        );
    }

    private function statusFor(int $candidateIndex, int $applicationIndex): ApplicationStatus
    {
        $statuses = [
            ApplicationStatus::Submitted,
            ApplicationStatus::Viewed,
            ApplicationStatus::Shortlisted,
            ApplicationStatus::Interview,
            ApplicationStatus::Rejected,
            ApplicationStatus::Accepted,
        ];

        return $statuses[($candidateIndex + $applicationIndex) % count($statuses)];
    }

    /**
     * @param  array<string, mixed>  $persona
     */
    private function applicationMessage(array $persona, Job $job, int $applicationIndex): string
    {
        $focus = $persona['skills'][$applicationIndex % count($persona['skills'])];

        return "Aplic pentru rolul {$job->title}. Experienta mea in {$focus} si proiectele recente descrise in profil se potrivesc cu responsabilitatile publicate.";
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function candidatePersonas(): array
    {
        return [
            ['name' => 'Radu Marinescu', 'email' => 'radu.marinescu@hireme.local', 'location' => 'Cluj-Napoca', 'headline' => 'Backend Engineer cu experienta in produse B2B', 'summary' => 'Lucreaza cu API-uri, baze de date si sisteme distribuite pentru platforme SaaS.', 'skills' => ['Laravel', 'PostgreSQL', 'Redis', 'API design'], 'experience' => [['title' => 'Backend Engineer', 'company' => 'SoftBridge', 'years' => 5]]],
            ['name' => 'Ioana Dumitrescu', 'email' => 'ioana.dumitrescu@hireme.local', 'location' => 'Bucuresti', 'headline' => 'Product Manager orientat pe discovery si metrics', 'summary' => 'Coordoneaza roadmap-uri si initiative de produs pentru echipe cross-functionale.', 'skills' => ['Discovery', 'Roadmap', 'Analytics', 'Stakeholders'], 'experience' => [['title' => 'Product Manager', 'company' => 'Mercury Apps', 'years' => 6]]],
            ['name' => 'Andrei Stoica', 'email' => 'andrei.stoica@hireme.local', 'location' => 'Iasi', 'headline' => 'QA Automation Engineer pentru aplicatii web', 'summary' => 'Construieste suite de testare stabile si sprijina livrari predictibile.', 'skills' => ['Playwright', 'API testing', 'CI/CD', 'SQL'], 'experience' => [['title' => 'QA Automation Engineer', 'company' => 'TestLab', 'years' => 4]]],
            ['name' => 'Elena Pavel', 'email' => 'elena.pavel@hireme.local', 'location' => 'Timisoara', 'headline' => 'UX/UI Designer pentru platforme operationale', 'summary' => 'Creeaza fluxuri clare pentru produse cu densitate mare de informatie.', 'skills' => ['Figma', 'Design systems', 'Research', 'Prototyping'], 'experience' => [['title' => 'UX Designer', 'company' => 'Studio Forma', 'years' => 5]]],
            ['name' => 'Mihnea Tudor', 'email' => 'mihnea.tudor@hireme.local', 'location' => 'Remote, Romania', 'headline' => 'DevOps Engineer cloud-native', 'summary' => 'Automatizeaza infrastructura si observabilitatea pentru echipe distribuite.', 'skills' => ['Docker', 'Terraform', 'Kubernetes', 'Monitoring'], 'experience' => [['title' => 'DevOps Engineer', 'company' => 'CloudForge', 'years' => 7]]],
            ['name' => 'Cristina Neagu', 'email' => 'cristina.neagu@hireme.local', 'location' => 'Brasov', 'headline' => 'Customer Success Manager pentru clienti B2B', 'summary' => 'Creste adoptia, retentia si satisfactia clientilor enterprise.', 'skills' => ['Onboarding', 'CRM', 'Account planning', 'Training'], 'experience' => [['title' => 'Customer Success Manager', 'company' => 'CarePilot', 'years' => 6]]],
            ['name' => 'Alexandru Ene', 'email' => 'alexandru.ene@hireme.local', 'location' => 'Bucuresti', 'headline' => 'Data Analyst cu focus pe BI si raportare', 'summary' => 'Construieste dashboard-uri si modele de date pentru decizii operationale.', 'skills' => ['SQL', 'Power BI', 'dbt', 'Statistics'], 'experience' => [['title' => 'Data Analyst', 'company' => 'MetricWorks', 'years' => 4]]],
            ['name' => 'Diana Ilie', 'email' => 'diana.ilie@hireme.local', 'location' => 'Cluj-Napoca', 'headline' => 'Recruitment Specialist pentru roluri tech si business', 'summary' => 'Gestioneaza sourcing, screening si experienta candidatilor end-to-end.', 'skills' => ['Sourcing', 'ATS', 'Interviewing', 'Candidate experience'], 'experience' => [['title' => 'Recruiter', 'company' => 'PeopleScale', 'years' => 5]]],
            ['name' => 'Sorin Matei', 'email' => 'sorin.matei@hireme.local', 'location' => 'Constanta', 'headline' => 'Operations Manager pentru procese scalabile', 'summary' => 'Optimizeaza procese, furnizori si indicatori de performanta.', 'skills' => ['KPI tracking', 'Planning', 'Process improvement', 'Vendor management'], 'experience' => [['title' => 'Operations Manager', 'company' => 'FlowOps', 'years' => 8]]],
            ['name' => 'Mara Pop', 'email' => 'mara.pop@hireme.local', 'location' => 'Remote, Romania', 'headline' => 'Content Marketing Specialist B2B', 'summary' => 'Scrie continut orientat pe conversie, SEO si educarea clientilor.', 'skills' => ['SEO', 'Copywriting', 'Analytics', 'Editorial calendar'], 'experience' => [['title' => 'Content Specialist', 'company' => 'BrandNorth', 'years' => 4]]],
            ['name' => 'Vlad Georgescu', 'email' => 'vlad.georgescu@hireme.local', 'location' => 'Oradea', 'headline' => 'Security Analyst pentru monitorizare si raspuns', 'summary' => 'Investigheaza alerte, vulnerabilitati si incidente de securitate.', 'skills' => ['SIEM', 'Linux', 'Incident response', 'Vulnerability management'], 'experience' => [['title' => 'Security Analyst', 'company' => 'SafeLayer', 'years' => 5]]],
            ['name' => 'Bianca Stan', 'email' => 'bianca.stan@hireme.local', 'location' => 'Sibiu', 'headline' => 'Finance Controller cu experienta in raportare', 'summary' => 'Coordoneaza bugete, forecast-uri si control financiar pentru echipe in crestere.', 'skills' => ['Budgeting', 'Forecasting', 'Excel', 'IFRS'], 'experience' => [['title' => 'Finance Controller', 'company' => 'LedgerPro', 'years' => 7]]],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function roles(): array
    {
        return [
            ['title' => 'Senior Laravel Engineer', 'level' => 'senior', 'salary_min' => 18000, 'salary_spread' => 8000, 'mission' => 'dezvolta servicii PHP robuste si API-uri curate', 'skills' => ['Laravel', 'MySQL', 'Redis', 'REST API']],
            ['title' => 'Frontend Vue Developer', 'level' => 'mid', 'salary_min' => 13500, 'salary_spread' => 6000, 'mission' => 'construieste interfete rapide si accesibile', 'skills' => ['Vue', 'TypeScript', 'Tailwind CSS', 'Vite']],
            ['title' => 'Product Manager', 'level' => 'senior', 'salary_min' => 17000, 'salary_spread' => 7000, 'mission' => 'transforma oportunitati de business in roadmap clar', 'skills' => ['Discovery', 'Roadmapping', 'Analytics', 'Stakeholder management']],
            ['title' => 'QA Automation Engineer', 'level' => 'mid', 'salary_min' => 12500, 'salary_spread' => 5500, 'mission' => 'automatizeaza testarea fluxurilor critice', 'skills' => ['Playwright', 'API testing', 'CI/CD', 'SQL']],
            ['title' => 'DevOps Engineer', 'level' => 'senior', 'salary_min' => 19000, 'salary_spread' => 8500, 'mission' => 'administreaza pipeline-uri si infrastructura cloud', 'skills' => ['Docker', 'Kubernetes', 'Terraform', 'Monitoring']],
            ['title' => 'Data Analyst', 'level' => 'mid', 'salary_min' => 12000, 'salary_spread' => 5000, 'mission' => 'extrage insight-uri actionabile din date operationale', 'skills' => ['SQL', 'Power BI', 'dbt', 'Statistics']],
            ['title' => 'UX/UI Designer', 'level' => 'mid', 'salary_min' => 11500, 'salary_spread' => 5000, 'mission' => 'proiecteaza experiente clare pentru utilizatori reali', 'skills' => ['Figma', 'User flows', 'Design systems', 'Prototyping']],
            ['title' => 'Customer Success Manager', 'level' => 'mid', 'salary_min' => 9500, 'salary_spread' => 4500, 'mission' => 'creste adoptia produsului si sanatatea portofoliului', 'skills' => ['Onboarding', 'Account planning', 'CRM', 'Communication']],
            ['title' => 'Sales Development Representative', 'level' => 'junior', 'salary_min' => 6500, 'salary_spread' => 3500, 'mission' => 'deschide conversatii comerciale cu clienti relevanti', 'skills' => ['Prospecting', 'HubSpot', 'Email outreach', 'Qualification']],
            ['title' => 'HR Business Partner', 'level' => 'senior', 'salary_min' => 12500, 'salary_spread' => 5500, 'mission' => 'sprijina managerii in decizii de oameni si cultura', 'skills' => ['Employee relations', 'Workforce planning', 'Coaching', 'HR analytics']],
            ['title' => 'Recruitment Specialist', 'level' => 'mid', 'salary_min' => 8500, 'salary_spread' => 4000, 'mission' => 'gestioneaza procese de recrutare end-to-end', 'skills' => ['Sourcing', 'Interviewing', 'ATS', 'Candidate experience']],
            ['title' => 'Content Marketing Specialist', 'level' => 'mid', 'salary_min' => 8000, 'salary_spread' => 3800, 'mission' => 'creeaza continut care sustine pipeline-ul comercial', 'skills' => ['SEO', 'Copywriting', 'Editorial calendar', 'Analytics']],
            ['title' => 'Performance Marketing Manager', 'level' => 'senior', 'salary_min' => 13000, 'salary_spread' => 6000, 'mission' => 'optimizeaza campanii platite pe canale digitale', 'skills' => ['Google Ads', 'Meta Ads', 'CRO', 'Attribution']],
            ['title' => 'Backend Node.js Engineer', 'level' => 'mid', 'salary_min' => 14500, 'salary_spread' => 6500, 'mission' => 'livreaza servicii backend scalabile', 'skills' => ['Node.js', 'PostgreSQL', 'Queues', 'OpenAPI']],
            ['title' => 'Mobile Flutter Developer', 'level' => 'mid', 'salary_min' => 14000, 'salary_spread' => 6500, 'mission' => 'dezvolta aplicatii mobile stabile si elegante', 'skills' => ['Flutter', 'Dart', 'REST API', 'Mobile UX']],
            ['title' => 'Security Analyst', 'level' => 'mid', 'salary_min' => 13500, 'salary_spread' => 6000, 'mission' => 'monitorizeaza riscuri si investigheaza alerte de securitate', 'skills' => ['SIEM', 'Incident response', 'Vulnerability management', 'Linux']],
            ['title' => 'Business Analyst', 'level' => 'mid', 'salary_min' => 10500, 'salary_spread' => 5000, 'mission' => 'clarifica procese si cerinte pentru echipe tehnice', 'skills' => ['Process mapping', 'User stories', 'UAT', 'Documentation']],
            ['title' => 'Scrum Master', 'level' => 'senior', 'salary_min' => 13000, 'salary_spread' => 5500, 'mission' => 'imbunatateste ritmul si claritatea echipelor agile', 'skills' => ['Facilitation', 'Agile coaching', 'Metrics', 'Conflict resolution']],
            ['title' => 'Finance Controller', 'level' => 'senior', 'salary_min' => 14000, 'salary_spread' => 6500, 'mission' => 'asigura control financiar si raportare predictibila', 'skills' => ['Budgeting', 'Forecasting', 'IFRS', 'Excel']],
            ['title' => 'Operations Manager', 'level' => 'senior', 'salary_min' => 14500, 'salary_spread' => 6500, 'mission' => 'optimizeaza procese operationale cross-functional', 'skills' => ['Process improvement', 'KPI tracking', 'Vendor management', 'Planning']],
            ['title' => 'Technical Support Engineer', 'level' => 'junior', 'salary_min' => 7000, 'salary_spread' => 3500, 'mission' => 'rezolva probleme tehnice pentru clienti B2B', 'skills' => ['Troubleshooting', 'SQL basics', 'Logs', 'Customer communication']],
            ['title' => 'Machine Learning Engineer', 'level' => 'senior', 'salary_min' => 21000, 'salary_spread' => 9500, 'mission' => 'duce modele ML din experiment in productie', 'skills' => ['Python', 'PyTorch', 'MLOps', 'Feature engineering']],
            ['title' => 'Data Engineer', 'level' => 'senior', 'salary_min' => 18500, 'salary_spread' => 8000, 'mission' => 'construieste pipeline-uri de date fiabile', 'skills' => ['Python', 'Airflow', 'BigQuery', 'Data modeling']],
            ['title' => 'Legal Counsel', 'level' => 'senior', 'salary_min' => 15000, 'salary_spread' => 7000, 'mission' => 'gestioneaza contracte si conformitate comerciala', 'skills' => ['Commercial contracts', 'GDPR', 'Negotiation', 'Compliance']],
            ['title' => 'Office Manager', 'level' => 'junior', 'salary_min' => 6000, 'salary_spread' => 3000, 'mission' => 'mentine biroul organizat si experienta interna fluida', 'skills' => ['Administration', 'Vendor coordination', 'Events', 'Procurement']],
            ['title' => 'Account Executive', 'level' => 'mid', 'salary_min' => 10500, 'salary_spread' => 6500, 'mission' => 'inchide oportunitati comerciale consultative', 'skills' => ['Discovery calls', 'Negotiation', 'CRM hygiene', 'Forecasting']],
            ['title' => 'Implementation Consultant', 'level' => 'mid', 'salary_min' => 11500, 'salary_spread' => 5500, 'mission' => 'ghideaza clientii in configurarea si lansarea produsului', 'skills' => ['Workshops', 'Configuration', 'Training', 'Project delivery']],
            ['title' => 'Community Manager', 'level' => 'junior', 'salary_min' => 6500, 'salary_spread' => 3200, 'mission' => 'creste comunitati active in jurul brandului', 'skills' => ['Social media', 'Moderation', 'Events', 'Content planning']],
            ['title' => 'Graphic Designer', 'level' => 'mid', 'salary_min' => 8500, 'salary_spread' => 4000, 'mission' => 'produce materiale vizuale consistente si memorabile', 'skills' => ['Adobe CC', 'Brand systems', 'Layout', 'Illustration']],
            ['title' => 'Payroll Specialist', 'level' => 'mid', 'salary_min' => 8000, 'salary_spread' => 3800, 'mission' => 'proceseaza salarizarea si documentele de personal', 'skills' => ['Payroll', 'Labor law basics', 'Excel', 'Attention to detail']],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function contexts(): array
    {
        return [
            ['title_suffix' => 'Core Platform', 'product' => 'platforma principala', 'team' => 'echipa de produs core'],
            ['title_suffix' => 'Marketplace Growth', 'product' => 'zona de marketplace si crestere', 'team' => 'echipa growth'],
            ['title_suffix' => 'Client Operations', 'product' => 'fluxurile operationale pentru clienti', 'team' => 'echipa operations'],
            ['title_suffix' => 'Data Products', 'product' => 'produsele bazate pe date', 'team' => 'echipa data si analytics'],
            ['title_suffix' => 'Enterprise Solutions', 'product' => 'solutiile enterprise', 'team' => 'echipa enterprise delivery'],
        ];
    }

    /**
     * @param  array<string, mixed>  $role
     * @param  array<string, string>  $context
     */
    private function jobDescription(array $role, array $context, string $companyName): string
    {
        $skills = implode(', ', $role['skills']);

        return <<<TEXT
Descrierea rolului
{$companyName} cauta un profil {$role['level']} care sa {$role['mission']} pentru {$context['product']}. Vei lucra cu {$context['team']} intr-un mediu orientat pe rezultate, claritate si colaborare.

Responsabilitati
- Livrezi initiative concrete, cu impact masurabil pentru clienti si echipa.
- Colaborezi cu produs, design, engineering, operations si stakeholderi de business.
- Documentezi deciziile importante si comunici transparent riscurile, dependintele si progresul.
- Propui imbunatatiri pentru procese, calitate si experienta utilizatorilor.

Cerinte
- Experienta relevanta pentru nivelul {$role['level']} si autonomie in activitatea de zi cu zi.
- Cunostinte solide de {$skills}.
- Capacitatea de a prioritiza, de a cere clarificari si de a lucra cu feedback rapid.
- Romana si engleza la nivel profesional.

Beneficii
- Range salarial transparent, brut lunar, stabilit in functie de experienta si interviu.
- Buget de invatare, zile libere suplimentare si echipament modern.
- Proces de recrutare clar: screening HR, discutie tehnica sau functionala si oferta.
TEXT;
    }
}
