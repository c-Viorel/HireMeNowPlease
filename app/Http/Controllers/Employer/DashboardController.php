<?php

namespace App\Http\Controllers\Employer;

use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Job;
use App\Models\Message;
use App\Support\Insights\CompanyResponsivenessScorer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, CompanyResponsivenessScorer $responsivenessScorer): View
    {
        $employerId = $request->user()->id;

        $companies = Company::query()
            ->where('owner_id', $employerId)
            ->withCount([
                'jobs',
                'jobs as active_jobs_count' => fn ($query) => $query->where('status', JobStatus::Published),
            ])
            ->latest()
            ->take(5)
            ->get();

        $activeJobs = Job::query()
            ->whereHas('company', fn ($query) => $query->where('owner_id', $employerId))
            ->where('status', JobStatus::Published)
            ->with('company')
            ->withCount('applications')
            ->latest('published_at')
            ->take(5)
            ->get();

        $latestMessages = Message::query()
            ->with(['sender', 'conversation.application.job.company'])
            ->whereHas('conversation.application.job.company', fn ($query) => $query->where('owner_id', $employerId))
            ->latest()
            ->take(5)
            ->get();

        $responseHealth = $companies
            ->map(fn (Company $company) => [
                'company' => $company,
                'score' => $responsivenessScorer->scoreCompany($company),
            ])
            ->values();

        return view('employer.dashboard', [
            'companies' => $companies,
            'activeJobs' => $activeJobs,
            'latestMessages' => $latestMessages,
            'responseHealth' => $responseHealth,
        ]);
    }
}
