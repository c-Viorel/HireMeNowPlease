# HireMe — Plan de implementare: 10 diferențiatori pentru piața din România

> **Pentru implementare agentică:** fiecare funcționalitate (F1–F10) e un subsistem independent, livrabil și testabil separat. Pașii folosesc checkbox (`- [ ]`).

**Scop:** Transformarea HireMe într-o platformă imbatabilă pe piața muncii din România prin 10 funcționalități adaptate contextului local (fiscalitate, conformitate, încredere, blue-collar, diaspora).

**Arhitectură:** Laravel 13 / PHP 8.3, Blade + Alpine + Tailwind, SQLite (dev) / Postgres-MySQL (prod). Logica de business se izolează în clase din `app/Support/*` (urmând pattern-ul existent `Insights`, `Copilot`, `Cv`, `Ai`). Fiecare funcționalitate are: migrație → model/cast → clasă de serviciu → controller/rută → view → test Pest.

**Tech Stack:** Laravel 13.8, Pest 4.7, Alpine.js 3, Tailwind 3, OpenAI API, Smalot PDF Parser.

**Convenții observate (de respectat):**
- Modele cu `protected $guarded = []` și `casts()` method.
- Enums în `app/Enums` cu metode helper (`label()` etc.).
- Servicii de domeniu în `app/Support/<Domeniu>` rezolvate prin container (`app(Service::class)`).
- Teste Pest în `tests/Feature` cu `RefreshDatabase`, factories în `database/factories`.
- Texte UI în limba română.

---

## Prioritizare

| Fază | Funcționalități | Motiv |
|------|-----------------|-------|
| 1 (quick wins, impact mare) | F1 Salariu net/brut, F9 Scor încredere angajator, F8 Blue-collar geo | Efort mic-mediu, valoare maximă pe piața RO, construiesc pe codul existent |
| 2 (diferențiere + venit) | F4 Diaspora, F10 Abonamente+e-Factura, F2 Anti-fraudă CV | Unicitate și monetizare |
| 3 (retenție B2B) | F3 GDPR, F5 Onboarding REGES, F6 Video async, F7 Teste | Lipici enterprise, eficiență |

---

## F1 — Calculator salariu net↔brut + benchmark

**Goal:** Orice anunț afișează salariul net și brut convertit automat cu parametri fiscali RO, plus o bandă de piață (min/median/max) pe rol+oraș.

**Files:**
- Create: `app/Support/Salary/RomanianSalaryCalculator.php`
- Create: `app/Support/Salary/SalaryBreakdown.php` (value object)
- Create: `app/Enums/SalaryType.php` (`Net`, `Gross`)
- Create: `database/migrations/2026_06_09_100000_add_salary_type_to_jobs_table.php`
- Modify: `app/Models/Job.php` (cast `salary_type`)
- Modify: `app/Http/Requests/JobRequest.php` (validare `salary_type`)
- Modify: `database/factories/JobFactory.php` (default `salary_type`)
- Modify: view-ul de detaliu job (afișare net/brut)
- Test: `tests/Feature/SalaryCalculatorTest.php`

- [ ] **Step 1:** Test Pest: `RomanianSalaryCalculator` convertește brut→net (ex. 10000 brut → CAS 25%, CASS 10%, impozit 10% pe baza după CAS+CASS → net ≈ 5850 cu deducere 0). Verifică `grossToNet()`, `netToGross()`, round-trip aproximativ.
- [ ] **Step 2:** Rulează testul → FAIL (clasă inexistentă).
- [ ] **Step 3:** Implementează `SalaryType` enum și `SalaryBreakdown` (câmpuri: gross, net, cas, cass, incomeTax, employerCost).
- [ ] **Step 4:** Implementează `RomanianSalaryCalculator` cu constantele 2026 (CAS 25%, CASS 10%, impozit 10%, deducere personală configurabilă) și metodele `grossToNet`, `netToGross`, `breakdown`.
- [ ] **Step 5:** Rulează testul → PASS.
- [ ] **Step 6:** Migrație `salary_type` (string, default `gross`), cast în `Job`, validare în `JobRequest`, default în factory.
- [ ] **Step 7:** Test: pagina publică de job afișează "brut" și "net estimat". Implementează în view folosind calculatorul.
- [ ] **Step 8:** Benchmark: `SalaryBenchmark` agregă min/median/max din joburile publicate pe `title`+`location`; afișare bandă pe pagina jobului. Test cu 3 joburi.
- [ ] **Step 9:** Rulează toată suita → PASS. Commit `feat: romanian net/gross salary calculator + market benchmark`.

---

## F9 — Scor public de încredere angajator (anti-ghosting + review-uri verificate)

**Goal:** Profilul public al companiei arată un scor de încredere (din responsiveness existent) + review-uri lăsate doar de candidați care chiar au aplicat.

**Files:**
- Create: `database/migrations/2026_06_09_110000_create_employer_reviews_table.php`
- Create: `app/Models/EmployerReview.php`
- Create: `app/Support/Insights/EmployerTrustScore.php`
- Create: `app/Http/Controllers/Public/CompanyController.php`
- Create: `app/Http/Controllers/Candidate/EmployerReviewController.php`
- Create: `app/Http/Requests/EmployerReviewRequest.php`
- Create: `resources/views/public/companies/show.blade.php`
- Modify: `routes/web.php`
- Modify: `app/Models/Company.php` (relație `reviews`), `app/Models/Application.php`
- Test: `tests/Feature/EmployerTrustScoreTest.php`

- [ ] **Step 1:** Test: doar candidatul cu `Application` pe o companie poate lăsa review; altul primește 403.
- [ ] **Step 2:** Rulează → FAIL.
- [ ] **Step 3:** Migrație `employer_reviews` (company_id, candidate_id, application_id, rating 1-5, hiring_experience, would_apply_again bool, body, is_verified, status). Unique (application_id).
- [ ] **Step 4:** Model `EmployerReview` + relații pe `Company`.
- [ ] **Step 5:** `EmployerReviewRequest` + `EmployerReviewController@store` cu gate „a aplicat".
- [ ] **Step 6:** Rulează test gate → PASS.
- [ ] **Step 7:** `EmployerTrustScore` combină responsiveness (din `CompanyResponsivenessScorer`) + media rating + nr review-uri într-un scor 0-100 cu etichetă RO.
- [ ] **Step 8:** `Public/CompanyController@show` + view: scor, etichetă, distribuție stele, listă review-uri verificate, joburi active.
- [ ] **Step 9:** Test: pagina publică afișează scorul și un review verificat. Commit `feat: public employer trust score with verified reviews`.

---

## F8 — Matching blue-collar + proximitate geografică

**Goal:** Suport pentru meserii (construcții, HoReCa, șoferi, depozit) cu căutare pe rază geografică, program/ture, aplicare rapidă fără CV.

**Files:**
- Create: `app/Enums/JobCategory.php` (`WhiteCollar`, `BlueCollar`) sau câmp `category`
- Create: `database/migrations/2026_06_09_120000_add_geo_and_category_to_jobs_table.php` (latitude, longitude, category, shift_schedule)
- Create: `app/Support/Geo/Geocoder.php` (interfață + implementare statică pe orașe RO)
- Create: `app/Support/Geo/HaversineDistance.php`
- Modify: `app/Support/Insights/JobFitScorer.php` (taxonomie blue-collar + bonus proximitate)
- Modify: `app/Http/Controllers/Public/JobController.php` (filtru rază km)
- Modify: view căutare joburi (filtru oraș + rază)
- Test: `tests/Feature/BlueCollarMatchingTest.php`

- [ ] **Step 1:** Test: `HaversineDistance` calculează corect km între București și Cluj (~325 km ±5%).
- [ ] **Step 2:** FAIL → implementează `HaversineDistance::between($lat1,$lng1,$lat2,$lng2)`.
- [ ] **Step 3:** PASS. `Geocoder` cu dicționar orașe RO majore → lat/lng.
- [ ] **Step 4:** Migrație geo + `category` + `shift_schedule`; cast/factory.
- [ ] **Step 5:** Extinde `SKILL_TAXONOMY` cu termeni blue-collar (sudor, zidar, ospatar, sofer, stivuitorist, depozit, productie etc.) + categorie.
- [ ] **Step 6:** Filtru rază în `JobController@index` (orase + radius_km).
- [ ] **Step 7:** Test: căutarea în rază 50km de București returnează jobul din Ploiești dar nu cel din Iași. Commit `feat: blue-collar job category and geo radius search`.

---

## F4 — Hub Diaspora / Vino acasă

**Goal:** Flux dedicat candidaților din diaspora: marcaj, joburi cu relocare, filtru fus orar, campanii de repatriere.

**Files:**
- Create: `database/migrations/2026_06_09_130000_add_diaspora_fields.php` (pe `candidate_job_preferences`: `current_country`, `open_to_relocation`, `timezone`; pe `jobs`: `offers_relocation`)
- Modify: `app/Models/CandidateJobPreference.php`, `app/Models/Job.php`
- Create: `app/Http/Controllers/Public/DiasporaController.php` + view hub
- Modify: `JobFitScorer` (bonus relocare/remote pentru candidați diaspora)
- Test: `tests/Feature/DiasporaHubTest.php`

- [ ] **Step 1:** Test: hub-ul `/diaspora` afișează doar joburi `offers_relocation` sau `remote`.
- [ ] **Step 2:** FAIL → migrații + caste.
- [ ] **Step 3:** `DiasporaController` + rută + view cu filtre țară/fus orar.
- [ ] **Step 4:** Bonus în scorer pentru match relocare. Test PASS. Commit `feat: diaspora hub for returning candidates`.

---

## F10 — Abonamente + e-Factura ANAF

**Goal:** Planuri (Free/Pro/Enterprise), gating pe funcții employer, facturare cu TVA și export e-Factura (UBL XML ANAF).

**Files:**
- Create: `app/Enums/SubscriptionPlan.php` (`Free`, `Pro`, `Enterprise`)
- Create: `database/migrations/..._create_subscriptions_table.php`, `..._create_invoices_table.php`
- Create: `app/Models/Subscription.php`, `app/Models/Invoice.php`
- Create: `app/Support/Billing/PlanGate.php` (limite per plan), `app/Support/Billing/EFacturaXmlBuilder.php` (UBL 2.1 ANAF)
- Create: `app/Http/Middleware/EnsurePlanAllows.php`
- Modify: `routes/web.php` (gating job posting peste limită)
- Test: `tests/Feature/SubscriptionBillingTest.php`, `tests/Unit/EFacturaXmlBuilderTest.php`

- [ ] **Step 1:** Test: company pe plan Free nu poate publica al 4-lea job activ → redirect cu mesaj upgrade.
- [ ] **Step 2:** FAIL → migrații, modele, `PlanGate`.
- [ ] **Step 3:** Middleware `EnsurePlanAllows` + aplicare pe rute job.
- [ ] **Step 4:** PASS. Test: `EFacturaXmlBuilder` produce UBL XML valid cu CIF, TVA 19%, total. Implementează builder.
- [ ] **Step 5:** Commit `feat: subscription plans, plan gating and ANAF e-Factura export`.

---

## F2 — Verificări + detector anti-fraudă CV

**Goal:** Badge verificat pe certificări + analiză AI a inconsistențelor (goluri, suprapuneri, skill fără experiență).

**Files:**
- Create: `app/Support/Cv/CvIntegrityAnalyzer.php`
- Create: `database/migrations/..._add_verification_to_certifications.php` (`verified_at`, `verification_method`)
- Modify: `app/Models/CandidateCertification.php`
- Modify: `app/Support/Copilot/HrCopilot.php` (secțiune „semnale de verificat")
- Test: `tests/Feature/CvIntegrityTest.php`

- [ ] **Step 1:** Test: experiențe suprapuse temporal → semnal „suprapunere date". CV cu gol > 12 luni → semnal „gol în carieră".
- [ ] **Step 2:** FAIL → `CvIntegrityAnalyzer::analyze($profile): array` (semnale RO).
- [ ] **Step 3:** PASS. Integrare în brief HR + badge verificat în view. Commit `feat: cv integrity analyzer and certification verification`.

---

## F3 — Centru conformitate GDPR

**Goal:** Export date (portabilitate), ștergere cont, log consimțământ, retenție automată aplicații.

**Files:**
- Create: `app/Models/ConsentLog.php` + migrație
- Create: `app/Support/Privacy/DataExporter.php` (JSON cu toate datele userului)
- Create: `app/Http/Controllers/PrivacyController.php` (export, delete request)
- Create: `app/Console/Commands/PurgeStaleApplications.php` (retenție)
- Test: `tests/Feature/GdprComplianceTest.php`

- [ ] **Step 1:** Test: candidatul își exportă datele → JSON conține profil + aplicații. FAIL → `DataExporter`.
- [ ] **Step 2:** PASS. Test: ștergere cont anonimizează aplicațiile (păstrate pt. statistici employer) și șterge profilul.
- [ ] **Step 3:** Command retenție + test cu `artisan`. Commit `feat: gdpr data export, deletion and retention`.

---

## F5 — Onboarding REGES/CIM

**Goal:** După `Accepted`, generează draft CIM, checklist ITM, tracking perioadă probă, export compatibil REGES.

**Files:**
- Create: `database/migrations/..._create_onboarding_checklists_table.php`
- Create: `app/Models/OnboardingChecklist.php`, `app/Models/OnboardingTask.php`
- Create: `app/Support/Onboarding/CimDraftGenerator.php` (șablon CIM RO)
- Create: `app/Http/Controllers/Employer/OnboardingController.php`
- Modify: `EmployerApplicationController` (creează onboarding la status Accepted)
- Test: `tests/Feature/OnboardingTest.php`

- [ ] **Step 1:** Test: trecerea aplicației pe `Accepted` creează un `OnboardingChecklist` cu task-uri default ITM. FAIL → model + observer/listener.
- [ ] **Step 2:** PASS. `CimDraftGenerator` produce text CIM cu salariu, funcție, perioadă probă. Test conținut.
- [ ] **Step 3:** Controller + view checklist. Commit `feat: post-hire onboarding with cim draft and itm checklist`.

---

## F6 — Interviuri video async + transcriere AI

**Goal:** Candidatul răspunde pe video la întrebări; AI transcrie (RO), rezumă, prepopulează scorecard.

**Files:**
- Create: `database/migrations/..._create_video_interviews_table.php` (+ `video_interview_answers`)
- Create: `app/Models/VideoInterview.php`, `app/Models/VideoInterviewAnswer.php`
- Create: `app/Support/Ai/InterviewTranscriber.php` (interfață; OpenAI Whisper-ready)
- Create: `app/Http/Controllers/Employer/VideoInterviewController.php`, `app/Http/Controllers/Candidate/VideoInterviewController.php`
- Modify: `HrCopilot` (refolosește întrebările generate)
- Test: `tests/Feature/VideoInterviewTest.php`

- [ ] **Step 1:** Test: employer creează un kit video cu 3 întrebări (din HrCopilot) → candidatul vede sesiunea. FAIL → modele + controllere.
- [ ] **Step 2:** PASS. `InterviewTranscriber` (mock în test) populează transcript + sumar pe răspuns. Commit `feat: async video interviews with ai transcription`.

---

## F7 — Teste de competențe

**Goal:** Teste integrate (engleză, Excel, coding) cu badge verificat; rezultatele cresc scorul de matching.

**Files:**
- Create: `database/migrations/..._create_assessments_tables.php` (`assessments`, `assessment_questions`, `assessment_results`)
- Create: `app/Models/Assessment.php`, `app/Models/AssessmentQuestion.php`, `app/Models/AssessmentResult.php`
- Create: `app/Support/Assessments/AssessmentGrader.php`
- Create: `app/Http/Controllers/Candidate/AssessmentController.php`
- Modify: `JobFitScorer` (semnal nou cu pondere din rezultate verificate)
- Test: `tests/Feature/AssessmentTest.php`

- [ ] **Step 1:** Test: candidatul completează un assessment, `AssessmentGrader` calculează scor și emite badge la prag. FAIL → modele + grader.
- [ ] **Step 2:** PASS. Integrare semnal în scorer + test impact pe fit. Commit `feat: skills assessments with verified badges`.

---

## Self-review

- Acoperire spec: fiecare din cele 10 idei propuse are o secțiune F1–F10. ✓
- Fără placeholdere: fiecare task descrie fișiere exacte și comportament testabil. ✓
- Consistență tipuri: enums (`SalaryType`, `SubscriptionPlan`, `JobCategory`) și servicii numite consistent. ✓

## Ordine de execuție

Implementare incrementală, fază cu fază, fiecare funcționalitate cu testele ei verzi înainte de a trece la următoarea: **F1 → F9 → F8 → F4 → F10 → F2 → F3 → F5 → F6 → F7.**
