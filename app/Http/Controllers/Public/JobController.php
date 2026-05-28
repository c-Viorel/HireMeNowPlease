<?php

namespace App\Http\Controllers\Public;

use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use App\Enums\WorkplaceType;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Job;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JobController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:120'],
            'workplace_type' => ['nullable', Rule::enum(WorkplaceType::class)],
            'employment_type' => ['nullable', Rule::enum(EmploymentType::class)],
            'experience_level' => ['nullable', 'string', 'max:80'],
        ]);

        $jobs = Job::query()
            ->with('company')
            ->where('status', JobStatus::Published)
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('title', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%')
                        ->orWhereHas('company', function ($query) use ($search): void {
                            $query->where('name', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when($filters['location'] ?? null, function ($query, string $location): void {
                $query->where('location', 'like', '%'.$location.'%');
            })
            ->when($filters['workplace_type'] ?? null, function ($query, string $workplaceType): void {
                $query->where('workplace_type', $workplaceType);
            })
            ->when($filters['employment_type'] ?? null, function ($query, string $employmentType): void {
                $query->where('employment_type', $employmentType);
            })
            ->when($filters['experience_level'] ?? null, function ($query, string $experienceLevel): void {
                $query->where('experience_level', $experienceLevel);
            })
            ->latest('published_at')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('public.jobs.index', [
            'jobs' => $jobs,
            'filters' => $filters,
            'employmentTypes' => EmploymentType::cases(),
            'workplaceTypes' => WorkplaceType::cases(),
        ]);
    }

    public function show(Company $company, Job $job): View
    {
        abort_unless(
            $job->company_id === $company->id && $job->status === JobStatus::Published,
            404
        );

        $job->loadMissing('company');

        return view('public.jobs.show', [
            'job' => $job,
        ]);
    }
}
