<?php

namespace App\Http\Requests;

use App\Enums\EmploymentType;
use App\Enums\WorkplaceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_id' => [
                'required',
                'integer',
                Rule::exists('companies', 'id')->where('owner_id', $this->user()->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['required', Rule::in(array_column(EmploymentType::cases(), 'value'))],
            'workplace_type' => ['required', Rule::in(array_column(WorkplaceType::cases(), 'value'))],
            'experience_level' => ['nullable', 'string', 'max:255'],
            'salary_min' => ['nullable', 'integer', 'min:0'],
            'salary_max' => ['nullable', 'integer', 'min:0', 'gte:salary_min'],
            'status' => ['required', Rule::in(['draft', 'published'])],
        ];
    }
}
