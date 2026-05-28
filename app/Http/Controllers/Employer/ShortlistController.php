<?php

namespace App\Http\Controllers\Employer;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Support\Shortlists;
use Illuminate\Http\RedirectResponse;

class ShortlistController extends Controller
{
    public function store(Application $application): RedirectResponse
    {
        $this->authorizeOwner($application);

        Shortlists::createForApplication($application);

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
