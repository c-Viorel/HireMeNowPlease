<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Support\Assessments\AssessmentGrader;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    public function index(Request $request): View
    {
        $profile = $request->user()->candidateProfile;

        $assessments = Assessment::query()->withCount('questions')->orderBy('title')->get();
        $results = $profile
            ? $profile->assessmentResults()->get()->keyBy('assessment_id')
            : collect();

        return view('candidate.assessments.index', [
            'assessments' => $assessments,
            'results' => $results,
        ]);
    }

    public function show(Assessment $assessment): View
    {
        return view('candidate.assessments.show', [
            'assessment' => $assessment->load('questions'),
        ]);
    }

    public function submit(Request $request, Assessment $assessment, AssessmentGrader $grader): RedirectResponse
    {
        $profile = $request->user()->candidateProfile;

        abort_if($profile === null, 403, 'Completeaza profilul inainte de a sustine un test.');

        $validated = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $result = $grader->grade($assessment, $profile, $validated['answers']);

        return redirect()
            ->route('candidate.assessments.index')
            ->with('status', $result->passed
                ? 'Felicitari! Ai obtinut badge-ul verificat pentru '.$assessment->title.'.'
                : 'Scor '.$result->score.'%. Mai incearca pentru a obtine badge-ul.');
    }
}
