<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\CandidateProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CandidateProfile>
 */
class CandidateProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(['role' => UserRole::Candidate]),
            'phone' => fake()->phoneNumber(),
            'location' => fake()->city(),
            'headline' => fake()->jobTitle(),
            'summary' => fake()->paragraph(),
            'experience' => [
                [
                    'title' => fake()->jobTitle(),
                    'company' => fake()->company(),
                    'years' => fake()->numberBetween(1, 6),
                ],
            ],
            'skills' => fake()->words(5),
            'cv_path' => null,
        ];
    }
}
