<?php

namespace App\Http\Controllers\Employer;

use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\JobRequest;
use App\Models\Company;
use App\Models\Job;
use App\Support\Billing\PlanGate;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class JobController extends Controller
{
    private const SLUG_RETRY_ATTEMPTS = 3;

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(array_column(JobStatus::cases(), 'value'))],
            'per_page' => ['nullable', 'integer', Rule::in([10, 25, 50, 100])],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);

        $jobs = Job::query()
            ->whereHas('company', fn ($query) => $query->where('owner_id', $request->user()->id))
            ->with('company')
            ->withCount('applications')
            ->when($filters['q'] ?? null, fn ($query, string $search) => $query->where('title', 'like', '%'.$search.'%'))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('employer.jobs.index', [
            'jobs' => $jobs,
            'filters' => $filters,
            'statuses' => JobStatus::cases(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('employer.jobs.create', [
            'companies' => $this->companiesFor($request),
        ]);
    }

    public function store(JobRequest $request, PlanGate $planGate): RedirectResponse
    {
        $validated = $request->validated();

        if ($validated['status'] === 'published') {
            $company = Company::query()->whereKey((int) $validated['company_id'])->first();

            if ($company && ! $planGate->canPublishJob($company)) {
                $plan = $planGate->planFor($company);

                return redirect()->back()->withInput()->withErrors([
                    'status' => 'Ai atins limita de '.$plan->activeJobLimit().' joburi active pentru planul '.$plan->label().'. Fa upgrade pentru a publica mai multe.',
                ]);
            }
        }

        $this->withUniqueSlugRetry(
            $validated['title'],
            (int) $validated['company_id'],
            null,
            fn (string $slug) => Job::create($this->jobData($validated, null, $slug))
        );

        return redirect()->route('employer.jobs.index')
            ->with('status', 'job-created');
    }

    public function show(Job $job): View
    {
        $this->authorizeOwner($job);

        return view('employer.jobs.show', [
            'job' => $job->load(['company', 'applications.candidate']),
        ]);
    }

    public function edit(Request $request, Job $job): View
    {
        $this->authorizeOwner($job);

        return view('employer.jobs.edit', [
            'job' => $job,
            'companies' => $this->companiesFor($request),
        ]);
    }

    public function update(JobRequest $request, Job $job): RedirectResponse
    {
        $this->authorizeOwner($job);

        $validated = $request->validated();
        $this->withUniqueSlugRetry(
            $validated['title'],
            (int) $validated['company_id'],
            $job,
            function (string $slug) use ($validated, $job): Job {
                $job->update($this->jobData($validated, $job, $slug));

                return $job;
            }
        );

        return redirect()->route('employer.jobs.index')
            ->with('status', 'job-updated');
    }

    public function destroy(Job $job): RedirectResponse
    {
        $this->authorizeOwner($job);
        $job->delete();

        return redirect()->route('employer.jobs.index')
            ->with('status', 'job-deleted');
    }

    private function companiesFor(Request $request)
    {
        return $request->user()->companies()->orderBy('name')->get();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function jobData(array $validated, ?Job $job = null, ?string $slug = null): array
    {
        $companyId = (int) $validated['company_id'];
        $status = $validated['status'];
        $publishedAt = $status === 'published'
            ? ($job?->published_at ?? now())
            : null;

        return [
            'company_id' => $companyId,
            'title' => $validated['title'],
            'slug' => $slug ?? $this->uniqueSlug($validated['title'], $companyId, $job),
            'description' => $validated['description'],
            'location' => $validated['location'] ?? null,
            'employment_type' => $validated['employment_type'],
            'workplace_type' => $validated['workplace_type'],
            'experience_level' => $validated['experience_level'] ?? null,
            'salary_min' => $validated['salary_min'] ?? null,
            'salary_max' => $validated['salary_max'] ?? null,
            'status' => $status,
            'published_at' => $publishedAt,
        ];
    }

    private function authorizeOwner(Job $job): void
    {
        abort_unless($job->company()->where('owner_id', auth()->id())->exists(), 403);
    }

    private function uniqueSlug(string $title, int $companyId, ?Job $ignore = null, bool $freshSuffix = false): string
    {
        $base = Str::slug($title) ?: 'job';
        $slug = $freshSuffix ? $base.'-'.Str::lower(Str::random(8)) : $base;
        $counter = 2;

        while (Job::query()
            ->where('company_id', $companyId)
            ->where('slug', $slug)
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * @template TReturn
     *
     * @param  callable(string): TReturn  $operation
     * @return TReturn
     */
    private function withUniqueSlugRetry(string $title, int $companyId, ?Job $ignore, callable $operation): mixed
    {
        for ($attempt = 0; $attempt < self::SLUG_RETRY_ATTEMPTS; $attempt++) {
            try {
                return $operation($this->uniqueSlug($title, $companyId, $ignore, $attempt > 0));
            } catch (QueryException $exception) {
                if (! $this->isUniqueConstraintViolation($exception) || $attempt === self::SLUG_RETRY_ATTEMPTS - 1) {
                    throw $exception;
                }
            }
        }
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        $driverCode = (string) ($exception->errorInfo[1] ?? '');
        $message = Str::lower($exception->getMessage());

        return in_array($sqlState, ['23000', '23505'], true)
            || in_array($driverCode, ['1062', '1555', '2067'], true)
            || str_contains($message, 'unique constraint')
            || str_contains($message, 'duplicate entry');
    }
}
