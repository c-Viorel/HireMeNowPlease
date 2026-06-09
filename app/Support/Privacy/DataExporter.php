<?php

namespace App\Support\Privacy;

use App\Models\User;

class DataExporter
{
    /**
     * Build a portable export of all personal data we hold for a user.
     *
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        $user->loadMissing(['candidateProfile', 'applications.job.company']);

        return [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'profile' => $user->candidateProfile?->snapshot(),
            'applications' => $user->applications->map(fn ($application) => [
                'job' => $application->job?->title,
                'company' => $application->job?->company?->name,
                'status' => $application->status->value,
                'message' => $application->message,
                'submitted_at' => $application->created_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }
}
