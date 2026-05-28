<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Http\Requests\JobRequest;
use App\Models\Company;
use App\Models\Job;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class JobController extends Controller
{
    public function index(Request $request): View
    {
        $jobs = Job::query()
            ->whereHas('company', fn ($query) => $query->where('owner_id', $request->user()->id))
            ->with('company')
            ->withCount('applications')
            ->latest()
            ->paginate(10);

        return view('employer.jobs.index', ['jobs' => $jobs]);
    }

    public function create(Request $request): View
    {
        return view('employer.jobs.create', [
            'companies' => $this->companiesFor($request),
        ]);
    }

    public function store(JobRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Job::create($this->jobData($validated));

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
        $data = $this->jobData($validated, $job);

        $job->update($data);

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
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function jobData(array $validated, ?Job $job = null): array
    {
        $companyId = (int) $validated['company_id'];
        $status = $validated['status'];
        $publishedAt = $status === 'published'
            ? ($job?->published_at ?? now())
            : null;

        return [
            'company_id' => $companyId,
            'title' => $validated['title'],
            'slug' => $this->uniqueSlug($validated['title'], $companyId, $job),
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

    private function uniqueSlug(string $title, int $companyId, ?Job $ignore = null): string
    {
        $base = Str::slug($title) ?: 'job';
        $slug = $base;
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
}
