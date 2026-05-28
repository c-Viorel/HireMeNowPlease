<?php

namespace App\Http\Controllers\Employer;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Shortlist;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $applications = Application::query()
            ->with(['candidate', 'candidateProfile', 'job.company'])
            ->whereHas('job.company', fn ($query) => $query->where('owner_id', $request->user()->id))
            ->latest()
            ->paginate(10);

        return view('employer.applications.index', [
            'applications' => $applications,
        ]);
    }

    public function show(Application $application): View
    {
        $this->authorizeOwner($application);

        return view('employer.applications.show', [
            'application' => $application->load(['candidate', 'candidateProfile', 'job.company']),
            'statuses' => $this->statusValues(),
        ]);
    }

    public function updateStatus(Request $request, Application $application): RedirectResponse
    {
        $this->authorizeOwner($application);

        $validated = $request->validate([
            'status' => ['required', Rule::in($this->statusValues())],
        ]);

        $application->update(['status' => $validated['status']]);

        if ($validated['status'] === ApplicationStatus::Shortlisted->value) {
            $this->shortlist($application);
        }

        return back()->with('status', 'application-status-updated');
    }

    private function authorizeOwner(Application $application): void
    {
        abort_unless(
            $application->job()
                ->whereHas('company', fn ($query) => $query->where('owner_id', auth()->id()))
                ->exists(),
            403
        );
    }

    /**
     * @return array<int, string>
     */
    private function statusValues(): array
    {
        return [
            ApplicationStatus::Viewed->value,
            ApplicationStatus::Shortlisted->value,
            ApplicationStatus::Interview->value,
            ApplicationStatus::Rejected->value,
            ApplicationStatus::Accepted->value,
        ];
    }

    private function shortlist(Application $application): void
    {
        $application->loadMissing('job');

        Shortlist::updateOrCreate([
            'company_id' => $application->job->company_id,
            'job_id' => $application->job_id,
            'candidate_id' => $application->candidate_id,
        ]);
    }
}

