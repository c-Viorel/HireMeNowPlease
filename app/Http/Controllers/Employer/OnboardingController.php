<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\OnboardingTask;
use App\Support\Onboarding\CimDraftGenerator;
use App\Support\Onboarding\OnboardingProvisioner;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function show(Application $application, CimDraftGenerator $cimGenerator, OnboardingProvisioner $provisioner): View
    {
        $this->authorizeOwner($application);

        $checklist = $provisioner->provision($application->loadMissing('job.company', 'candidate'));

        return view('employer.onboarding.show', [
            'application' => $application,
            'checklist' => $checklist->load('tasks'),
            'cimDraft' => $cimGenerator->forApplication($application),
        ]);
    }

    public function toggleTask(Request $request, Application $application, OnboardingTask $task): RedirectResponse
    {
        $this->authorizeOwner($application);

        abort_unless($task->checklist->application_id === $application->id, 403);

        $task->update(['is_done' => ! $task->is_done]);

        return back()->with('status', 'onboarding-task-updated');
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
}
