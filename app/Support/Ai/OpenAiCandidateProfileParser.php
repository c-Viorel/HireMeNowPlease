<?php

namespace App\Support\Ai;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiCandidateProfileParser
{
    /**
     * @return array<string, mixed>
     */
    public function parse(string $cvText): array
    {
        $apiKey = config('services.openai.key');

        if (! $apiKey) {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $response = Http::timeout(75)
            ->withToken($apiKey)
            ->acceptJson()
            ->post('https://api.openai.com/v1/responses', [
                'model' => config('services.openai.model', 'gpt-4.1-mini'),
                'input' => [
                    [
                        'role' => 'system',
                        'content' => 'You extract candidate profile data from CV text. Return concise, factual JSON only. Use null or empty arrays when data is not present. Do not invent employers, dates, salaries, links, or certifications.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Extract a structured candidate profile and CV improvement analysis from this CV text:\n\n".$cvText,
                    ],
                ],
                'text' => [
                    'format' => [
                        'type' => 'json_schema',
                        'name' => 'candidate_profile_extract',
                        'description' => 'Candidate profile data extracted from a CV plus CV improvement analysis.',
                        'schema' => $this->schema(),
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('OpenAI could not analyze this CV right now.');
        }

        return $this->normalize($this->decodeResponse($response->json()));
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['headline', 'summary', 'phone', 'location', 'skills', 'experiences', 'educations', 'certifications', 'links', 'job_preference', 'cv_analysis'],
            'properties' => [
                'headline' => ['type' => 'string'],
                'summary' => ['type' => 'string'],
                'phone' => ['type' => 'string'],
                'location' => ['type' => 'string'],
                'skills' => ['type' => 'array', 'items' => ['type' => 'string']],
                'experiences' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['title', 'company', 'employment_type', 'location', 'workplace_type', 'start_date', 'end_date', 'is_current', 'description', 'skills'],
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'company' => ['type' => 'string'],
                            'employment_type' => ['type' => 'string'],
                            'location' => ['type' => 'string'],
                            'workplace_type' => ['type' => 'string'],
                            'start_date' => ['type' => 'string'],
                            'end_date' => ['type' => 'string'],
                            'is_current' => ['type' => 'boolean'],
                            'description' => ['type' => 'string'],
                            'skills' => ['type' => 'array', 'items' => ['type' => 'string']],
                        ],
                    ],
                ],
                'educations' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['institution', 'degree', 'field_of_study', 'start_date', 'end_date', 'is_current', 'description'],
                        'properties' => [
                            'institution' => ['type' => 'string'],
                            'degree' => ['type' => 'string'],
                            'field_of_study' => ['type' => 'string'],
                            'start_date' => ['type' => 'string'],
                            'end_date' => ['type' => 'string'],
                            'is_current' => ['type' => 'boolean'],
                            'description' => ['type' => 'string'],
                        ],
                    ],
                ],
                'certifications' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['name', 'issuer', 'issued_at', 'expires_at', 'credential_url'],
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'issuer' => ['type' => 'string'],
                            'issued_at' => ['type' => 'string'],
                            'expires_at' => ['type' => 'string'],
                            'credential_url' => ['type' => 'string'],
                        ],
                    ],
                ],
                'links' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['label', 'url'],
                        'properties' => [
                            'label' => ['type' => 'string'],
                            'url' => ['type' => 'string'],
                        ],
                    ],
                ],
                'job_preference' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['availability', 'experience_level', 'desired_salary_min', 'desired_salary_max', 'preferred_workplace_types', 'preferred_employment_types'],
                    'properties' => [
                        'availability' => ['type' => 'string'],
                        'experience_level' => ['type' => 'string'],
                        'desired_salary_min' => ['type' => 'integer'],
                        'desired_salary_max' => ['type' => 'integer'],
                        'preferred_workplace_types' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'preferred_employment_types' => ['type' => 'array', 'items' => ['type' => 'string']],
                    ],
                ],
                'cv_analysis' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['score', 'strengths', 'improvements', 'rewrite_suggestions'],
                    'properties' => [
                        'score' => ['type' => 'integer'],
                        'strengths' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'improvements' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'rewrite_suggestions' => ['type' => 'array', 'items' => ['type' => 'string']],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function decodeResponse(array $payload): array
    {
        $text = $payload['output_text'] ?? null;

        foreach ($payload['output'] ?? [] as $output) {
            foreach ($output['content'] ?? [] as $content) {
                if (($content['type'] ?? null) === 'output_text') {
                    $text = $content['text'] ?? $text;
                }
            }
        }

        $data = json_decode((string) $text, true);

        if (! is_array($data)) {
            throw new RuntimeException('OpenAI returned an unreadable CV analysis.');
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        $data['headline'] = mb_substr((string) ($data['headline'] ?? ''), 0, 160);
        $data['summary'] = mb_substr((string) ($data['summary'] ?? ''), 0, 3000);
        $data['phone'] = mb_substr((string) ($data['phone'] ?? ''), 0, 30);
        $data['location'] = mb_substr((string) ($data['location'] ?? ''), 0, 120);
        $data['skills'] = $this->strings($data['skills'] ?? [], 12);
        $data['experiences'] = array_slice(array_values($data['experiences'] ?? []), 0, 8);
        $data['educations'] = array_slice(array_values($data['educations'] ?? []), 0, 5);
        $data['certifications'] = array_slice(array_values($data['certifications'] ?? []), 0, 6);
        $data['links'] = array_slice(array_values($data['links'] ?? []), 0, 6);
        $data['job_preference'] = is_array($data['job_preference'] ?? null) ? $data['job_preference'] : [];
        $data['cv_analysis'] = is_array($data['cv_analysis'] ?? null) ? $data['cv_analysis'] : [];
        $data['cv_analysis']['score'] = max(0, min(100, (int) ($data['cv_analysis']['score'] ?? 0)));
        $data['cv_analysis']['strengths'] = $this->strings($data['cv_analysis']['strengths'] ?? [], 5);
        $data['cv_analysis']['improvements'] = $this->strings($data['cv_analysis']['improvements'] ?? [], 5);
        $data['cv_analysis']['rewrite_suggestions'] = $this->strings($data['cv_analysis']['rewrite_suggestions'] ?? [], 5);

        return $data;
    }

    /**
     * @return array<int, string>
     */
    private function strings(mixed $value, int $limit): array
    {
        return collect(is_array($value) ? $value : [])
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->unique()
            ->take($limit)
            ->values()
            ->all();
    }
}
