<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $profile = $user->candidateProfile;

        $recentApplications = $user->applications()
            ->with('job.company')
            ->latest()
            ->take(5)
            ->get();

        $recentConversations = Conversation::query()
            ->with(['application.job.company'])
            ->whereHas('application', fn ($query) => $query->where('candidate_id', $user->id))
            ->latest()
            ->take(5)
            ->get();

        return view('candidate.dashboard', [
            'profile' => $profile,
            'profileCompletion' => $this->profileCompletion($profile),
            'recentApplications' => $recentApplications,
            'recentConversations' => $recentConversations,
        ]);
    }

    private function profileCompletion(mixed $profile): int
    {
        if (! $profile) {
            return 0;
        }

        $fields = [
            $profile->phone,
            $profile->location,
            $profile->headline,
            $profile->summary,
            $profile->skills,
            $profile->experience,
            $profile->cv_path,
        ];

        $completed = collect($fields)
            ->filter(fn ($value) => is_array($value) ? count($value) > 0 : filled($value))
            ->count();

        return (int) round(($completed / count($fields)) * 100);
    }
}
