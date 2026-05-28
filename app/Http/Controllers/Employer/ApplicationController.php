<?php

namespace App\Http\Controllers\Employer;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Notifications\ApplicationStatusChangedNotification;
use App\Support\Shortlists;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function downloadCv(Application $application): StreamedResponse
    {
        $this->authorizeOwner($application);

        abort_if(! $application->cv_path || ! Storage::disk('local')->exists($application->cv_path), 404);

        return Storage::disk('local')->download($application->cv_path, basename($application->cv_path));
    }

    public function updateStatus(Request $request, Application $application): RedirectResponse
    {
        $this->authorizeOwner($application);

        $validated = $request->validate([
            'status' => ['required', Rule::in($this->statusValues())],
        ]);

        $application->fill(['status' => $validated['status']]);
        $statusChanged = $application->isDirty('status');
        $application->save();

        if ($validated['status'] === ApplicationStatus::Shortlisted->value) {
            Shortlists::createForApplication($application);
        }

        if ($statusChanged) {
            $application->loadMissing('candidate');
            $application->candidate->notify(ApplicationStatusChangedNotification::fromApplication($application));
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

}
