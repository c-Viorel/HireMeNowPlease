<?php

namespace App\Http\Controllers\Employer;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Notifications\ApplicationStatusChangedNotification;
use App\Support\Copilot\HrCopilot;
use App\Support\Insights\JobFitScorer;
use App\Support\Onboarding\OnboardingProvisioner;
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
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(array_column(ApplicationStatus::cases(), 'value'))],
            'job' => ['nullable', 'integer'],
            'min_fit' => ['nullable', 'integer', 'min:0', 'max:100'],
            'sort' => ['nullable', Rule::in(['newest', 'oldest', 'fit_desc', 'fit_asc'])],
            'per_page' => ['nullable', 'integer', Rule::in([10, 25, 50, 100])],
            'view' => ['nullable', Rule::in(['list', 'kanban'])],
        ]);

        $ownerId = $request->user()->id;

        $baseQuery = Application::query()
            ->with(['candidate', 'candidateProfile', 'job.company'])
            ->whereHas('job.company', fn ($query) => $query->where('owner_id', $ownerId));

        $statsApplications = (clone $baseQuery)->get();

        $filteredQuery = (clone $baseQuery)
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->whereHas('candidate', fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'))
                        ->orWhereHas('job', fn ($q) => $q->where('title', 'like', '%'.$search.'%'));
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['job'] ?? null, fn ($query, int $jobId) => $query->where('job_id', $jobId))
            ->when(isset($filters['min_fit']), function ($query) use ($filters): void {
                $min = (int) $filters['min_fit'];
                $query->whereRaw("CAST(json_extract(fit_snapshot, '$.score') AS INTEGER) >= ?", [$min]);
            });

        $sort = $filters['sort'] ?? 'newest';
        match ($sort) {
            'oldest' => $filteredQuery->oldest(),
            'fit_desc' => $filteredQuery->orderByRaw("CAST(json_extract(fit_snapshot, '$.score') AS INTEGER) DESC")->latest(),
            'fit_asc' => $filteredQuery->orderByRaw("CAST(json_extract(fit_snapshot, '$.score') AS INTEGER) ASC")->latest(),
            default => $filteredQuery->latest(),
        };

        $openStatuses = [
            ApplicationStatus::Submitted->value,
            ApplicationStatus::Viewed->value,
            ApplicationStatus::Shortlisted->value,
            ApplicationStatus::Interview->value,
        ];

        $applicationStats = [
            'total' => $statsApplications->count(),
            'open' => $statsApplications->filter(fn (Application $application) => in_array($application->status->value, $openStatuses, true))->count(),
            'high_fit' => $statsApplications->filter(fn (Application $application) => (int) data_get($application->fit_snapshot, 'score', 0) >= 70)->count(),
            'needs_response' => $statsApplications->filter(fn (Application $application) => in_array($application->status->value, [
                ApplicationStatus::Submitted->value,
                ApplicationStatus::Viewed->value,
            ], true))->count(),
        ];

        $jobOptions = $statsApplications
            ->pluck('job')
            ->unique('id')
            ->sortBy('title')
            ->map(fn ($job) => ['id' => $job->id, 'title' => $job->title])
            ->values();

        if (($filters['view'] ?? 'list') === 'kanban') {
            $cards = $filteredQuery->get();

            return view('employer.applications.kanban', [
                'filters' => $filters,
                'jobOptions' => $jobOptions,
                'statuses' => ApplicationStatus::cases(),
                'applicationStats' => $applicationStats,
                'columns' => collect(ApplicationStatus::cases())->map(fn (ApplicationStatus $status) => [
                    'status' => $status,
                    'label' => str($status->value)->replace('_', ' ')->title(),
                    'applications' => $cards->filter(fn (Application $application) => $application->status === $status)->values(),
                ]),
                'statusTransitions' => $this->statusValues(),
            ]);
        }

        $perPage = (int) ($filters['per_page'] ?? 10);
        $applications = $filteredQuery->paginate($perPage)->withQueryString();

        return view('employer.applications.index', [
            'applications' => $applications,
            'filters' => $filters,
            'jobOptions' => $jobOptions,
            'statuses' => ApplicationStatus::cases(),
            'statusTransitions' => $this->statusValues(),
            'applicationStats' => $applicationStats,
        ]);
    }

    public function show(Application $application, HrCopilot $copilot, JobFitScorer $fitScorer): View
    {
        $this->authorizeOwner($application);

        return view('employer.applications.show', [
            'application' => $application->load([
                'candidate',
                'candidateProfile.experiences',
                'candidateProfile.educations',
                'candidateProfile.certifications',
                'candidateProfile.links',
                'candidateProfile.jobPreference',
                'job.company',
                'scorecard.items',
            ]),
            'statuses' => $this->statusValues(),
            'fitScore' => $application->fit_snapshot ?: $fitScorer->score($application->profile_snapshot ?: $application->candidateProfile?->snapshot(), $application->job)->toArray(),
            'copilotBrief' => $copilot->brief($application),
            'scorecardCriteria' => ['Role fit', 'Technical / functional depth', 'Communication', 'Ownership', 'Motivation'],
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

        $this->transitionStatus($application, $validated['status']);

        return back()->with('status', 'application-status-updated');
    }

    public function bulkUpdateStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'application_ids' => ['required', 'array', 'min:1'],
            'application_ids.*' => ['integer'],
            'status' => ['required', Rule::in($this->statusValues())],
        ]);

        $ownerId = $request->user()->id;

        $applications = Application::query()
            ->whereIn('id', $validated['application_ids'])
            ->whereHas('job.company', fn ($query) => $query->where('owner_id', $ownerId))
            ->get();

        foreach ($applications as $application) {
            $this->transitionStatus($application, $validated['status']);
        }

        return back()->with('status', 'applications-bulk-updated:'.$applications->count());
    }

    private function transitionStatus(Application $application, string $status): void
    {
        $application->fill(['status' => $status]);
        $statusChanged = $application->isDirty('status');
        $application->save();

        if ($status === ApplicationStatus::Shortlisted->value) {
            Shortlists::createForApplication($application);
        }

        if ($status === ApplicationStatus::Accepted->value) {
            app(OnboardingProvisioner::class)->provision($application);
        }

        if ($statusChanged) {
            $application->loadMissing('candidate');
            $application->candidate->notify(ApplicationStatusChangedNotification::fromApplication($application));
        }
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
