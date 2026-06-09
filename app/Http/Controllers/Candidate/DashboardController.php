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
            'nextActions' => $this->nextActions($profile, $recentApplications, $recentConversations, $bestMatches),
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

    private function nextActions(mixed $profile, mixed $recentApplications, mixed $recentConversations, mixed $bestMatches): array
    {
        $actions = [];

        if (! $profile || blank($profile->cv_path)) {
            $actions[] = [
                'label' => 'Importa CV-ul cu AI',
                'description' => 'Completeaza profilul rapid din PDF sau DOCX.',
                'href' => route('candidate.profile.ai.create'),
                'tone' => 'primary',
            ];
        }

        if ($profile && blank($profile->summary)) {
            $actions[] = [
                'label' => 'Adauga sumarul',
                'description' => '3-5 fraze cresc calitatea potrivirilor.',
                'href' => route('candidate.profile.edit').'#basic',
                'tone' => 'secondary',
            ];
        }

        if ($bestMatches->isNotEmpty()) {
            $actions[] = [
                'label' => 'Exploreaza potriviri',
                'description' => $bestMatches->first()['fit']['score'].'% cel mai bun fit disponibil acum.',
                'href' => route('jobs.index'),
                'tone' => 'secondary',
            ];
        }

        if ($recentConversations->isNotEmpty()) {
            $actions[] = [
                'label' => 'Verifica mesajele',
                'description' => 'Ai conversatii recente de urmarit.',
                'href' => route('conversations.index'),
                'tone' => 'secondary',
            ];
        }

        if ($actions === []) {
            $actions[] = [
                'label' => 'Cauta joburi',
                'description' => $recentApplications->isEmpty() ? 'Incepe cu rolurile publice verificate.' : 'Continua sa gasesti roluri compatibile.',
                'href' => route('jobs.index'),
                'tone' => 'primary',
            ];
        }

        return array_slice($actions, 0, 4);
    }
}
