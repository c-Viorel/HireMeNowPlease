<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\CandidateProfileRequest;
use App\Models\CandidateProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $oldCvPath = $profile?->cv_path;
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
            'cv_path' => $oldCvPath,
        ];

        if ($request->hasFile('cv')) {
            $data['cv_path'] = $request->file('cv')->store("cvs/{$userId}", 'local');
        }

        CandidateProfile::updateOrCreate(['user_id' => $userId], $data);

        if ($oldCvPath && $oldCvPath !== $data['cv_path'] && Storage::disk('local')->exists($oldCvPath)) {
            Storage::disk('local')->delete($oldCvPath);
        }

        return redirect()->route('candidate.profile.edit')
            ->with('status', 'candidate-profile-updated');
    }

    public function downloadCv(): StreamedResponse
    {
        $profile = auth()->user()->candidateProfile;

        abort_if(! $profile?->cv_path || ! Storage::disk('local')->exists($profile->cv_path), 404);

        return Storage::disk('local')->download($profile->cv_path);
    }
}
