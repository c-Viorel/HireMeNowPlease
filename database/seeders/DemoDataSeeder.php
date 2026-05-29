<?php

namespace Database\Seeders;

use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Enums\WorkplaceType;
use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\Job;
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

        CandidateProfile::updateOrCreate(
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
