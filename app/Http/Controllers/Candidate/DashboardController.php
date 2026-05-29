<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Job;
use App\Support\Copilot\CandidateCoach;
use App\Support\Insights\JobFitScorer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, CandidateCoach $candidateCoach, JobFitScorer $fitScorer): View
    {
        $user = $request->user();
        $profile = $user->candidateProfile?->loadMissing([
            'experiences',
            'educations',
            'certifications',
            'links',
            'jobPreference',
        ]);

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

        $bestMatches = collect();

        if ($profile) {
            $appliedJobIds = $user->applications()->pluck('job_id');
            $bestMatches = Job::query()
                ->with('company')
                ->publiclyVisible()
                ->whereNotIn('id', $appliedJobIds)
                ->latest('published_at')
                ->take(24)
                ->get()
                ->map(fn (Job $job) => [
                    'job' => $job,
                    'fit' => $fitScorer->score($profile, $job)->toArray(),
                ])
                ->sortByDesc('fit.score')
                ->take(4)
                ->values();
        }

        return view('candidate.dashboard', [
            'profile' => $profile,
            'profileCompletion' => $this->profileCompletion($profile),
            'profileCoach' => $candidateCoach->profileAdvice($profile),
            'bestMatches' => $bestMatches,
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
            $profile->experiences,
            $profile->educations,
            $profile->certifications,
            $profile->links,
            $profile->jobPreference,
            $profile->cv_path,
        ];

        $completed = collect($fields)
            ->filter(fn ($value) => is_countable($value) ? count($value) > 0 : filled($value))
            ->count();

        return (int) round(($completed / count($fields)) * 100);
    }
}
