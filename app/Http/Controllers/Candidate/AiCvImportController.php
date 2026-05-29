<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Support\Ai\OpenAiCandidateProfileParser;
use App\Support\Cv\CandidateProfileAiWriter;
use App\Support\Cv\CvTextExtractor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AiCvImportController extends Controller
{
    public function create(): View
    {
        return view('candidate.profile.ai.create');
    }

    public function preview(Request $request, CvTextExtractor $extractor, OpenAiCandidateProfileParser $parser): View|RedirectResponse
    {
        $validated = $request->validate([
            'cv' => ['required', 'file', 'mimes:pdf,docx', 'max:5120'],
        ]);

        if (! config('services.openai.key')) {
            throw ValidationException::withMessages([
                'cv' => 'AI CV import is not configured yet.',
            ]);
        }

        try {
            $text = $extractor->extract($validated['cv']);
            $data = $parser->parse($text);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'cv' => $exception->getMessage(),
            ]);
        }

        $temporaryPath = $validated['cv']->store("ai-cv-imports/{$request->user()->id}", 'local');

        $request->session()->put('candidate_ai_cv_import', [
            'data' => $data,
            'temporary_cv_path' => $temporaryPath,
            'original_name' => $validated['cv']->getClientOriginalName(),
        ]);

        return view('candidate.profile.ai.preview', [
            'data' => $data,
        ]);
    }

    public function apply(Request $request, CandidateProfileAiWriter $writer): RedirectResponse
    {
        $import = $request->session()->get('candidate_ai_cv_import');

        if (! is_array($import) || ! is_array($import['data'] ?? null)) {
            return redirect()->route('candidate.profile.ai.create')
                ->withErrors(['cv' => 'Upload a CV before applying AI extracted data.']);
        }

        $writer->save(
            $request->user(),
            $import['data'],
            $import['temporary_cv_path'] ?? null,
            $import['original_name'] ?? null
        );

        if (($import['temporary_cv_path'] ?? null) && Storage::disk('local')->exists($import['temporary_cv_path'])) {
            Storage::disk('local')->delete($import['temporary_cv_path']);
        }

        $request->session()->forget('candidate_ai_cv_import');

        return redirect()->route('candidate.profile.edit')
            ->with('status', 'ai-cv-imported');
    }
}
