<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CandidateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Candidate;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:30'],
            'location' => ['nullable', 'string', 'max:120'],
            'headline' => ['nullable', 'string', 'max:160'],
            'summary' => ['nullable', 'string', 'max:3000'],
            'skills' => ['nullable', 'string', 'max:1000'],
            'availability' => ['nullable', 'string', 'max:80'],
            'experience_level' => ['nullable', 'string', 'max:80'],
            'desired_salary_min' => ['nullable', 'integer', 'min:0'],
            'desired_salary_max' => ['nullable', 'integer', 'min:0', 'gte:desired_salary_min'],
            'preferred_workplace_types' => ['nullable', 'array'],
            'preferred_workplace_types.*' => ['string', Rule::in(['remote', 'hybrid', 'on_site'])],
            'preferred_employment_types' => ['nullable', 'array'],
            'preferred_employment_types.*' => ['string', Rule::in(['full_time', 'part_time', 'contract', 'internship'])],
            'experiences' => ['nullable', 'array', 'max:12'],
            'experiences.*.title' => ['nullable', 'string', 'max:160'],
            'experiences.*.company' => ['nullable', 'string', 'max:160'],
            'experiences.*.employment_type' => ['nullable', Rule::in(['full_time', 'part_time', 'contract', 'internship', 'freelance'])],
            'experiences.*.location' => ['nullable', 'string', 'max:120'],
            'experiences.*.workplace_type' => ['nullable', Rule::in(['remote', 'hybrid', 'on_site'])],
            'experiences.*.start_date' => ['nullable', 'date'],
            'experiences.*.end_date' => ['nullable', 'date'],
            'experiences.*.is_current' => ['nullable', 'boolean'],
            'experiences.*.description' => ['nullable', 'string', 'max:3000'],
            'experiences.*.skills' => ['nullable', 'string', 'max:1000'],
            'educations' => ['nullable', 'array', 'max:8'],
            'educations.*.institution' => ['nullable', 'string', 'max:180'],
            'educations.*.degree' => ['nullable', 'string', 'max:140'],
            'educations.*.field_of_study' => ['nullable', 'string', 'max:160'],
            'educations.*.start_date' => ['nullable', 'date'],
            'educations.*.end_date' => ['nullable', 'date'],
            'educations.*.is_current' => ['nullable', 'boolean'],
            'educations.*.description' => ['nullable', 'string', 'max:2000'],
            'certifications' => ['nullable', 'array', 'max:8'],
            'certifications.*.name' => ['nullable', 'string', 'max:180'],
            'certifications.*.issuer' => ['nullable', 'string', 'max:160'],
            'certifications.*.issued_at' => ['nullable', 'date'],
            'certifications.*.expires_at' => ['nullable', 'date'],
            'certifications.*.credential_url' => ['nullable', 'url', 'max:255'],
            'links' => ['nullable', 'array', 'max:8'],
            'links.*.label' => ['nullable', 'string', 'max:80'],
            'links.*.url' => ['nullable', 'url', 'max:255'],
            'cv' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->requireGroupedFields($validator, 'experiences', ['title', 'company', 'start_date']);
                $this->requireGroupedFields($validator, 'educations', ['institution']);
                $this->requireGroupedFields($validator, 'certifications', ['name']);
                $this->requireGroupedFields($validator, 'links', ['label', 'url']);
                $this->validateDateOrder($validator, 'experiences', 'start_date', 'end_date');
                $this->validateDateOrder($validator, 'educations', 'start_date', 'end_date');
                $this->validateDateOrder($validator, 'certifications', 'issued_at', 'expires_at');
            },
        ];
    }

    /**
     * @param  array<int, string>  $requiredFields
     */
    private function requireGroupedFields(Validator $validator, string $group, array $requiredFields): void
    {
        foreach ($this->input($group, []) as $index => $row) {
            if (! is_array($row) || ! collect($row)->filter(fn ($value) => filled($value))->isNotEmpty()) {
                continue;
            }

            foreach ($requiredFields as $field) {
                if (blank($row[$field] ?? null)) {
                    $validator->errors()->add("{$group}.{$index}.{$field}", 'This field is required for completed profile entries.');
                }
            }
        }
    }

    private function validateDateOrder(Validator $validator, string $group, string $startField, string $endField): void
    {
        foreach ($this->input($group, []) as $index => $row) {
            if (! is_array($row) || blank($row[$startField] ?? null) || blank($row[$endField] ?? null)) {
                continue;
            }

            if (strtotime((string) $row[$endField]) < strtotime((string) $row[$startField])) {
                $validator->errors()->add("{$group}.{$index}.{$endField}", 'The end date must be after or equal to the start date.');
            }
        }
    }
}
