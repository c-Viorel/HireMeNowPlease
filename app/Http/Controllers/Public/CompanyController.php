<?php

namespace App\Http\Controllers\Public;

use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\EmployerReview;
use App\Support\Insights\EmployerTrustScore;
use Illuminate\Contracts\View\View;

class CompanyController extends Controller
{
    public function show(Company $company, EmployerTrustScore $trustScore): View
    {
        abort_unless($company->status === 'approved', 404);

        $reviews = EmployerReview::query()
            ->where('company_id', $company->id)
            ->where('status', 'published')
            ->where('is_verified', true)
            ->with('candidate:id,name')
            ->latest()
            ->take(20)
            ->get();

        $jobs = $company->jobs()
            ->where('status', JobStatus::Published)
            ->latest('published_at')
            ->take(10)
            ->get();

        return view('public.companies.show', [
            'company' => $company,
            'trust' => $trustScore->forCompany($company),
            'reviews' => $reviews,
            'jobs' => $jobs,
        ]);
    }
}
