# Platform Differentiators Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build five standout product modules: anti-ghosting company score, explainable job fit score, HR Copilot, Candidate Career Coach, and structured interview scorecards, with a UI that makes these differentiators obvious from the first login.

**Architecture:** Add a shared insights layer that computes transparent scores from existing jobs, profiles, applications, messages, and status history. Keep AI-like outputs deterministic at first, using rules and templates, so the feature ships without needing external AI credentials; later these services can call an LLM behind the same interfaces.

**Tech Stack:** Laravel 12, Blade, Alpine.js, Tailwind CSS, MySQL/SQLite, Pest feature tests.

---

## Completion Status

- [x] Shared insight scoring foundation and application snapshots
- [x] Anti-ghosting company score on public and employer surfaces
- [x] Explainable job fit score on candidate-facing surfaces
- [x] HR Copilot and Candidate Career Coach
- [x] Structured interview scorecards
- [x] Demo data, automated tests, and production build verification

---

## Product Surface

The platform should visibly communicate that it is not just a job board. Add a new "Intelligence" layer across candidate, employer, and public surfaces:

- Candidate sees: fit explanation, career coach actions, transparent company response metrics.
- Employer sees: candidate summaries, shortlist reasons, interview scorecards, response-health nudges.
- Public job pages show: fit preview for logged-in candidates and employer transparency signals.
- Employer application review becomes the main command center for HR Copilot and structured interview decisions.

## UI Direction

Use a restrained operational interface: dense, calm, scan-friendly, with clear score panels and timeline blocks. Avoid marketing-style hero sections inside the logged-in app. The differentiators should appear as persistent product objects:

- `Insight cards`: small score panels with reason bullets.
- `Decision rails`: right-side panels on application/job pages with next actions.
- `Score badges`: compact, color-coded labels for fit, response health, and interview readiness.
- `Timeline blocks`: structured interview and application progress with status, owner, and due date.

Primary UI anchors:

- Candidate dashboard: "Your best matches", "Career coach", "Companies that respond".
- Job detail page: "Why this fits you" and "Company responsiveness".
- Employer dashboard: "Hiring health", "Response debt", "Interview pipeline".
- Employer application show: "HR Copilot", "Fit breakdown", "Interview scorecard".

---

## Phase 1: Shared Insight Foundation

### Task 1: Add Insight DTOs and Score Services

**Files:**
- Create: `app/Support/Insights/FitScore.php`
- Create: `app/Support/Insights/JobFitScorer.php`
- Create: `app/Support/Insights/CompanyResponsiveness.php`
- Create: `app/Support/Insights/CompanyResponsivenessScorer.php`
- Test: `tests/Feature/InsightScoringTest.php`

**Build:**
- `JobFitScorer` compares candidate profile skills, experience titles, preferred workplace type, preferred contract type, location, and salary expectations against a job.
- Return an explainable score object, not only a number.
- `CompanyResponsivenessScorer` computes average response time, response rate, open conversations without HR reply, and ghosting risk.

**Acceptance tests:**
- Candidate with matching skills and preferences gets high fit score.
- Missing must-have signals reduce score and appear in explanation.
- Company with fast replies and low ignored applications gets strong responsiveness score.
- Company with many unanswered applications gets weak responsiveness score.

### Task 2: Store Optional Insight Snapshots

**Files:**
- Create migration: `database/migrations/*_add_insight_snapshots_to_applications_table.php`
- Modify: `app/Models/Application.php`
- Modify: `app/Http/Controllers/Candidate/ApplicationController.php`
- Test: `tests/Feature/ApplicationWorkflowTest.php`

**Build:**
- Add nullable JSON columns:
  - `fit_snapshot`
  - `responsiveness_snapshot`
- When a candidate applies, store the current fit score and company responsiveness score.
- Keep snapshots stable even if candidate profile or company response history changes later.

**Acceptance tests:**
- Applying stores `fit_snapshot.score`, `fit_snapshot.matched`, `fit_snapshot.gaps`.
- Updating the candidate profile after applying does not change the stored snapshot.

---

## Phase 2: Anti-Ghosting Company Score

### Task 3: Public Company Response Metrics

**Files:**
- Create: `app/View/Components/CompanyResponsivenessCard.php`
- Create: `resources/views/components/company-responsiveness-card.blade.php`
- Modify: `resources/views/public/jobs/show.blade.php`
- Modify: `resources/views/public/jobs/index.blade.php`
- Test: `tests/Feature/PublicJobBoardTest.php`

**Build:**
- Show a compact transparency card:
  - "Usually replies in X days"
  - "Response rate: X%"
  - "Feedback consistency"
  - "Ghosting risk: Low / Medium / High"
- Use neutral language; do not shame companies aggressively.

**UI placement:**
- Job detail sidebar under company info.
- Job listing card as a small badge: `Fast responder`, `Usually replies`, or `New employer`.

**Acceptance tests:**
- Job detail displays response metrics for approved company.
- No-applications company displays "New employer" fallback.

### Task 4: Employer Response Health Dashboard

**Files:**
- Modify: `app/Http/Controllers/Employer/DashboardController.php`
- Modify: `resources/views/employer/dashboard.blade.php`
- Test: `tests/Feature/EmployerPortalTest.php`

**Build:**
- Add dashboard panel:
  - unanswered applications count
  - oldest unanswered candidate
  - response rate
  - suggested next action
- Highlight response debt without blocking workflow.

**Acceptance tests:**
- Employer with unanswered applications sees response debt panel.
- Employer with no pending replies sees healthy state.

---

## Phase 3: Explainable Job Fit Score

### Task 5: Candidate Fit on Job Pages

**Files:**
- Create: `app/View/Components/JobFitCard.php`
- Create: `resources/views/components/job-fit-card.blade.php`
- Modify: `app/Http/Controllers/Public/JobController.php`
- Modify: `resources/views/public/jobs/show.blade.php`
- Test: `tests/Feature/PublicJobBoardTest.php`

**Build:**
- For logged-in candidates, show:
  - fit percentage
  - top matched skills
  - gaps
  - salary/preference alignment
  - "Improve profile for this job" link
- For guests and employers, hide personal fit details.

**Acceptance tests:**
- Candidate sees fit card on job detail.
- Guest does not see candidate-specific fit.
- Employer does not see candidate-specific fit.

### Task 6: Best Matches on Candidate Dashboard

**Files:**
- Modify: `app/Http/Controllers/Candidate/DashboardController.php`
- Modify: `resources/views/candidate/dashboard.blade.php`
- Test: `tests/Feature/CandidatePortalTest.php`

**Build:**
- Add "Best matches for you" section with top 5 public jobs.
- Each item shows score and one-line reason.
- Sort by fit score, then recency.

**Acceptance tests:**
- Candidate dashboard displays highest matching job first.
- Jobs from inactive/blocked companies are excluded.

---

## Phase 4: HR Copilot

### Task 7: Candidate Summary and Interview Questions

**Files:**
- Create: `app/Support/Copilot/HrCandidateBrief.php`
- Create: `app/Support/Copilot/HrCopilot.php`
- Modify: `app/Http/Controllers/Employer/ApplicationController.php`
- Modify: `resources/views/employer/applications/show.blade.php`
- Test: `tests/Feature/EmployerPortalTest.php`

**Build:**
- On application review page, add HR Copilot panel:
  - 4-bullet candidate summary
  - reasons to advance
  - concerns to verify
  - suggested interview questions
  - suggested next action
- Generate deterministically from profile snapshot, job description, application message, and fit snapshot.

**Acceptance tests:**
- Employer application review shows candidate summary.
- Suggested questions reference candidate skills and job requirements.
- Non-owner employer cannot access copilot output.

### Task 8: JD Quality Helper

**Files:**
- Create: `app/Support/Copilot/JobDescriptionReview.php`
- Create: `app/Support/Copilot/JobDescriptionReviewer.php`
- Modify: `app/Http/Controllers/Employer/JobController.php`
- Modify: `resources/views/employer/jobs/create.blade.php`
- Modify: `resources/views/employer/jobs/edit.blade.php`
- Test: `tests/Feature/EmployerPortalTest.php`

**Build:**
- Show JD quality score on create/edit job form:
  - salary present
  - responsibilities clear
  - requirements realistic
  - work mode present
  - benefits present
- Start as server-rendered after validation/preview, not live JS.

**Acceptance tests:**
- Weak JD shows improvement suggestions.
- Strong JD shows high score.

---

## Phase 5: Candidate Career Coach

### Task 9: Profile Coach

**Files:**
- Create: `app/Support/Copilot/CandidateCoach.php`
- Modify: `app/Http/Controllers/Candidate/ProfileController.php`
- Modify: `resources/views/candidate/profile/edit.blade.php`
- Test: `tests/Feature/CandidatePortalTest.php`

**Build:**
- Add right-side "Profile coach" panel:
  - missing headline/about/skills sections
  - weak experience descriptions
  - missing dates
  - missing links/certifications
  - profile completion action list
- Keep the coach actionable, not generic.

**Acceptance tests:**
- Incomplete candidate profile shows targeted suggestions.
- Complete profile shows fewer/no critical suggestions.

### Task 10: Job-Specific Coach

**Files:**
- Modify: `app/Http/Controllers/Public/JobController.php`
- Modify: `resources/views/public/jobs/show.blade.php`
- Test: `tests/Feature/PublicJobBoardTest.php`

**Build:**
- Candidate sees "How to improve your chances" on job detail:
  - add missing skill to profile if true
  - update experience description
  - prepare for likely interview topic
  - salary alignment warning when relevant

**Acceptance tests:**
- Candidate missing a job skill sees a coach suggestion.
- Candidate with strong match sees preparation suggestions instead of profile warnings.

---

## Phase 6: Structured Interview Scorecards

### Task 11: Scorecard Data Model

**Files:**
- Create migration: `database/migrations/*_create_interview_scorecards_table.php`
- Create migration: `database/migrations/*_create_interview_scorecard_items_table.php`
- Create: `app/Models/InterviewScorecard.php`
- Create: `app/Models/InterviewScorecardItem.php`
- Modify: `app/Models/Application.php`
- Test: `tests/Feature/InterviewScorecardTest.php`

**Build:**
- A scorecard belongs to an application.
- Items include:
  - criterion
  - question
  - score 1-5 nullable
  - note
  - required boolean
- Scorecard includes recommendation: advance, hold, reject.

**Acceptance tests:**
- Employer can create a scorecard for owned application.
- Non-owner cannot read/write scorecard.
- Required criteria without score keep scorecard incomplete.

### Task 12: Scorecard UI on Application Review

**Files:**
- Create: `app/Http/Controllers/Employer/InterviewScorecardController.php`
- Modify: `routes/web.php`
- Modify: `resources/views/employer/applications/show.blade.php`
- Test: `tests/Feature/InterviewScorecardTest.php`

**Build:**
- Add scorecard section below HR Copilot.
- Pre-fill suggested criteria from job + fit gaps:
  - technical fit
  - communication
  - domain experience
  - salary alignment
  - availability
- Employer can save scores/notes and recommendation.

**Acceptance tests:**
- Scorecard form saves scores and notes.
- Application review displays saved recommendation.

---

## Phase 7: Feature Spotlight UI

### Task 13: Differentiator Navigation and Dashboard Highlights

**Files:**
- Modify: `resources/views/layouts/dashboard.blade.php`
- Modify: `resources/views/candidate/dashboard.blade.php`
- Modify: `resources/views/employer/dashboard.blade.php`
- Modify: `resources/css/app.css`
- Test: `tests/Feature/NavigationTest.php`

**Build:**
- Add visible but restrained "Insights" language:
  - Candidate dashboard: `Fit`, `Coach`, `Company response`
  - Employer dashboard: `Response health`, `Copilot`, `Scorecards`
- Use icon-like badges via CSS text labels or existing style primitives.
- Keep layout dense and professional.

**Acceptance tests:**
- Candidate dashboard shows feature highlights.
- Employer dashboard shows feature highlights.
- Navigation remains role-specific.

---

## Phase 8: Demo Data and Production Readiness

### Task 14: Seed Differentiator Demo Data

**Files:**
- Modify: `database/seeders/DemoDataSeeder.php`
- Test: `tests/Feature/DemoDataSeederTest.php`

**Build:**
- Add response histories that create different company responsiveness states.
- Ensure demo candidate has strong/medium/weak job fit examples.
- Add scorecards for several applications.
- Add copilot-friendly profile snapshots and application messages.

**Acceptance tests:**
- Demo seed creates anti-ghosting metrics.
- Demo seed creates fit snapshots for all applications.
- Demo seed creates at least 8 scorecards.
- Running seeder twice does not duplicate records.

### Task 15: Full Verification and Deploy

**Files:**
- No production code unless fixing failures.

**Commands:**
- `php artisan test`
- `npm run build`
- `git diff --check`
- `git status -sb`
- `git add ...`
- `git commit -m "feat: add recruitment intelligence layer"`
- `git push origin main`
- Watch GitHub Actions deploy.

**Production verification:**
- `https://hireme.vmedia.fun/login` returns 200.
- Candidate demo can see fit/coach panels.
- HR demo can see copilot/scorecard panels.
- NAS containers are healthy.
- New migrations show `Ran`.

---

## Suggested Sub-Agent Split

Use separate workers with non-overlapping ownership:

- Agent A: Insight scoring services and tests.
- Agent B: Anti-ghosting UI and employer response health.
- Agent C: Job fit UI and candidate best matches.
- Agent D: HR Copilot services and application review panel.
- Agent E: Candidate Career Coach.
- Agent F: Interview scorecards.
- Agent G: Demo data and final verification.

Integrate in this order: Foundation -> Anti-ghosting/Fit -> Copilots -> Scorecards -> Demo data -> UI polish.

## Risks

- LLM dependency risk: avoid external AI calls in v1; deterministic copilot keeps deploy simple.
- UI density risk: keep insights as cards/rails, not oversized marketing panels.
- Trust risk: scores must be explainable and editable; never present them as absolute truth.
- Data migration risk: scorecards and snapshots must be nullable/backward-compatible.
