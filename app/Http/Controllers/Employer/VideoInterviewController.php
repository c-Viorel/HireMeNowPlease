<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\VideoInterview;
use App\Models\VideoInterviewAnswer;
use App\Support\Ai\InterviewTranscriber;
use App\Support\Copilot\HrCopilot;
use Illuminate\Http\RedirectResponse;

class VideoInterviewController extends Controller
{
    public function store(Application $application, HrCopilot $copilot): RedirectResponse
    {
        $this->authorizeOwner($application);

        $interview = VideoInterview::firstOrCreate(
            ['application_id' => $application->id],
            ['status' => 'invited']
        );

        if ($interview->wasRecentlyCreated) {
            $questions = $copilot->brief($application)['questions'] ?? [];

            foreach (array_values($questions) as $index => $question) {
                $interview->answers()->create([
                    'question' => $question,
                    'sort_order' => $index,
                ]);
            }
        }

        return back()->with('status', 'video-interview-created');
    }

    public function transcribe(Application $application, VideoInterviewAnswer $answer, InterviewTranscriber $transcriber): RedirectResponse
    {
        $this->authorizeOwner($application);

        abort_unless($answer->interview->application_id === $application->id, 403);
        abort_if($answer->video_path === null, 422, 'Nu exista inregistrare de transcris.');

        $result = $transcriber->transcribe($answer->video_path);

        $answer->update([
            'transcript' => $result['transcript'],
            'summary' => $result['summary'],
        ]);

        return back()->with('status', 'video-answer-transcribed');
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
