<?php

namespace Database\Factories;

use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use App\Enums\WorkplaceType;
use App\Models\Company;
use App\Models\Job;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Job>
 */
class JobFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->jobTitle();

        return [
            'company_id' => Company::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1000, 9999),
            'description' => fake()->paragraphs(3, true),
            'location' => fake()->city(),
            'employment_type' => EmploymentType::FullTime,
            'workplace_type' => WorkplaceType::Hybrid,
            'experience_level' => 'mid',
            'salary_min' => null,
            'salary_max' => null,
            'status' => JobStatus::Published,
            'published_at' => now(),
        ];
    }
}
