<?php

namespace App\Http\Controllers\Employer;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Notifications\ApplicationStatusChangedNotification;
use App\Support\Shortlists;
use Illuminate\Http\RedirectResponse;

class ShortlistController extends Controller
{
    public function store(Application $application): RedirectResponse
    {
        $this->authorizeOwner($application);

        Shortlists::createForApplication($application);

        $application->fill(['status' => ApplicationStatus::Shortlisted]);
        $statusChanged = $application->isDirty('status');
        $application->save();

        if ($statusChanged) {
            $application->loadMissing('candidate');
            $application->candidate->notify(ApplicationStatusChangedNotification::fromApplication($application));
        }

        return back()->with('status', 'candidate-shortlisted');
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
