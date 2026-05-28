<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\CandidateProfileRequest;
use App\Models\CandidateProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('candidate.profile.edit', [
            'profile' => auth()->user()->candidateProfile,
        ]);
    }

    public function update(CandidateProfileRequest $request): RedirectResponse
    {
        $userId = auth()->id();
        $profile = auth()->user()->candidateProfile;
        $validated = $request->validated();

        $data = [
            'phone' => $validated['phone'] ?? null,
            'location' => $validated['location'] ?? null,
            'headline' => $validated['headline'] ?? null,
            'summary' => $validated['summary'] ?? null,
            'skills' => collect(explode(',', $validated['skills'] ?? ''))
                ->map(fn (string $skill) => trim($skill))
                ->filter()
                ->values()
                ->all(),
            'cv_path' => $profile?->cv_path,
        ];

        if ($request->hasFile('cv')) {
            $data['cv_path'] = $request->file('cv')->store("cvs/{$userId}");
        }

        CandidateProfile::updateOrCreate(['user_id' => $userId], $data);

        return redirect()->route('candidate.profile.edit')
            ->with('status', 'candidate-profile-updated');
    }
}
