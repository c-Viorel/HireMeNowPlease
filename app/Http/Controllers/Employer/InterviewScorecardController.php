<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InterviewScorecardController extends Controller
{
    public function update(Request $request, Application $application): RedirectResponse
    {
        $this->authorizeOwner($application);

        $validated = $request->validate([
            'recommendation' => ['required', Rule::in(['strong_yes', 'yes', 'hold', 'no'])],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:3'],
            'items.*.criterion' => ['required', 'string', 'max:120'],
            'items.*.score' => ['required', 'integer', 'min:1', 'max:5'],
            'items.*.evidence' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($application, $request, $validated): void {
            $overallScore = (int) round(collect($validated['items'])->avg('score') * 20);
            $scorecard = $application->scorecard()->updateOrCreate(
                ['reviewer_id' => $request->user()->id],
                [
                    'overall_score' => $overallScore,
                    'recommendation' => $validated['recommendation'],
                    'notes' => $validated['notes'] ?? null,
                    'completed_at' => now(),
                ]
            );

            $scorecard->items()->delete();

            foreach ($validated['items'] as $item) {
                $scorecard->items()->create([
                    'criterion' => $item['criterion'],
                    'score' => $item['score'],
                    'evidence' => $item['evidence'] ?? null,
                ]);
            }
        });

        return back()->with('status', 'scorecard-updated');
    }

    private function authorizeOwner(Application $application): void
    {
        abort_unless(
            $application->job()
                ->whereHas('company', fn ($query) => $query->where('owner_id', auth()->id()))
                ->exists(),
            403
        );
    }
}
