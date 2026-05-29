<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApplicationRequest;
use App\Models\Company;
use App\Models\Job;
use App\Notifications\NewApplicationNotification;
use App\Support\ApplicationSubmissions;
use App\Support\Insights\CompanyResponsivenessScorer;
use App\Support\Insights\JobFitScorer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

    public function store(
        ApplicationRequest $request,
        Company $company,
        Job $job,
        JobFitScorer $fitScorer,
        CompanyResponsivenessScorer $responsivenessScorer
    ): RedirectResponse
    {
        abort_unless(
            Job::query()
                ->publiclyVisible()
                ->whereKey($job->id)
                ->where('company_id', $company->id)
                ->exists(),
            404
        );

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

        $application = ApplicationSubmissions::create([
            'job_id' => $job->id,
            'candidate_id' => $candidate->id,
            'candidate_profile_id' => $profile->id,
            'message' => $request->validated('message'),
            'cv_path' => null,
            'profile_snapshot' => $profile->snapshot(),
            'fit_snapshot' => $fitScorer->score($profile, $job)->toArray(),
            'responsiveness_snapshot' => $responsivenessScorer->scoreJob($job),
            'status' => ApplicationStatus::Submitted,
        ]);

        if ($profile->cv_path && Storage::disk('local')->exists($profile->cv_path)) {
            $snapshotPath = 'applications/'.$application->id.'/'.basename($profile->cv_path);

            Storage::disk('local')->copy($profile->cv_path, $snapshotPath);
            $application->update(['cv_path' => $snapshotPath]);
        }

        $job->loadMissing('company.owner');
        $job->company->owner->notify(NewApplicationNotification::fromApplication($application));

        return redirect()->route('candidate.applications.index')
            ->with('status', 'application-submitted');
    }
}
