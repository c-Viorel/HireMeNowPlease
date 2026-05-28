<?php

namespace App\Http\Controllers\Employer;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Shortlist;
use Illuminate\Http\RedirectResponse;

class ShortlistController extends Controller
{
    public function store(Application $application): RedirectResponse
    {
        $this->authorizeOwner($application);
        $application->loadMissing('job');

        Shortlist::updateOrCreate([
            'company_id' => $application->job->company_id,
            'job_id' => $application->job_id,
            'candidate_id' => $application->candidate_id,
        ]);

        $application->update(['status' => ApplicationStatus::Shortlisted]);

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

