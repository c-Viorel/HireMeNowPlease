<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\VideoInterview;
use App\Models\VideoInterviewAnswer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VideoInterviewController extends Controller
{
    public function show(VideoInterview $interview): View
    {
        $this->authorizeCandidate($interview);

        return view('candidate.video.show', [
            'interview' => $interview->load('answers'),
        ]);
    }

    public function storeAnswer(Request $request, VideoInterview $interview, VideoInterviewAnswer $answer): RedirectResponse
    {
        $this->authorizeCandidate($interview);

        abort_unless($answer->video_interview_id === $interview->id, 403);

        $validated = $request->validate([
            'recording' => ['required', 'file', 'mimetypes:video/webm,video/mp4', 'max:51200'],
        ]);

        $path = $validated['recording']->store('videos/'.$interview->id, 'local');

        $answer->update(['video_path' => $path]);
        $interview->update(['status' => 'in_progress']);

        return back()->with('status', 'video-answer-saved');
    }

    private function authorizeCandidate(VideoInterview $interview): void
    {
        $interview->loadMissing('application');

        abort_unless($interview->application->candidate_id === auth()->id(), 403);
    }
}
