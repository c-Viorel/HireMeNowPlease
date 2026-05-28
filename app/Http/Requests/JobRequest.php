<?php

namespace App\Http\Requests;

use App\Enums\EmploymentType;
use App\Enums\WorkplaceType;
use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('status') !== 'published' || $validator->errors()->has('company_id')) {
                    return;
                }

                $company = Company::query()
                    ->whereKey($this->integer('company_id'))
                    ->where('owner_id', $this->user()->id)
                    ->first();

                if ($company && $company->status !== 'approved') {
                    $validator->errors()->add('status', 'Only jobs for approved companies can be published.');
                }
            },
        ];
    }
}
