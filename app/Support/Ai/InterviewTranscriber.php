<?php

namespace App\Support\Ai;

/**
 * Transcribes and summarizes a recorded interview answer.
 *
 * The default implementation is a safe placeholder; in production it can be
 * swapped (via the container) for an OpenAI Whisper + summarization pipeline.
 */
class InterviewTranscriber
{
    /**
     * @return array{transcript: string, summary: string}
     */
    public function transcribe(string $reference): array
    {
        return [
            'transcript' => 'Transcrierea va fi disponibila dupa procesarea inregistrarii ('.$reference.').',
            'summary' => 'Rezumat indisponibil momentan.',
        ];
    }
}
