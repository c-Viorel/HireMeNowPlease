<?php

namespace App\Support\Salary;

use App\Enums\JobStatus;
use App\Models\Job;
use Illuminate\Support\Str;

class SalaryBenchmark
{
    /**
     * Build a market salary band (min / median / max) for jobs sharing the
     * same normalized title and city as the given job.
     *
     * @return array{min: int, median: int, max: int, sample: int}|null
     */
    public function forJob(Job $job): ?array
    {
        $title = $this->normalize($job->title);

        if ($title === '') {
            return null;
        }

        $values = Job::query()
            ->where('status', JobStatus::Published)
            ->when($job->location, fn ($query) => $query->where('location', $job->location))
            ->whereNotNull('salary_min')
            ->whereNotNull('salary_max')
            ->get(['title', 'salary_min', 'salary_max'])
            ->filter(fn (Job $candidate) => $this->normalize($candidate->title) === $title)
            ->flatMap(fn (Job $candidate) => [(int) $candidate->salary_min, (int) $candidate->salary_max])
            ->filter(fn (int $value) => $value > 0)
            ->sort()
            ->values();

        if ($values->count() < 4) {
            return null;
        }

        return [
            'min' => (int) $values->first(),
            'median' => (int) round($values->median()),
            'max' => (int) $values->last(),
            'sample' => (int) ceil($values->count() / 2),
        ];
    }

    private function normalize(string $title): string
    {
        return Str::of($title)->lower()->squish()->value();
    }
}
