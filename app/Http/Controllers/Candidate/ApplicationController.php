<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApplicationRequest;
use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $applications = $request->user()
            ->applications()
            ->with('job.company')
            ->latest()
            ->paginate(10);

        return view('candidate.applications.index', [
            'applications' => $applications,
        ]);
    }

    public function store(ApplicationRequest $request, Company $company, Job $job): RedirectResponse
    {
        abort_unless($job->company_id === $company->id && $job->status === JobStatus::Published, 404);

        $candidate = $request->user();
        $profile = $candidate->candidateProfile;

        if (! $profile) {
            return back()->withErrors([
                'candidate_profile' => 'Complete your candidate profile before applying.',
            ])->withInput();
        }

        if ($job->applications()->where('candidate_id', $candidate->id)->exists()) {
            return back()->withErrors([
                'job' => 'You have already applied to this job.',
            ])->withInput();
        }

        Application::create([
            'job_id' => $job->id,
            'candidate_id' => $candidate->id,
            'candidate_profile_id' => $profile->id,
            'message' => $request->validated('message'),
            'cv_path' => $profile->cv_path,
            'status' => ApplicationStatus::Submitted,
        ]);

        return redirect()->route('candidate.applications.index')
            ->with('status', 'application-submitted');
    }
}

