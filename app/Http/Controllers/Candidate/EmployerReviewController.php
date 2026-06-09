<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployerReviewRequest;
use App\Models\Application;
use App\Models\Company;
use App\Models\EmployerReview;
use Illuminate\Http\RedirectResponse;

class EmployerReviewController extends Controller
{
    public function store(EmployerReviewRequest $request, Company $company): RedirectResponse
    {
        $application = Application::query()
            ->whereKey($request->integer('application_id'))
            ->where('candidate_id', $request->user()->id)
            ->whereHas('job', fn ($query) => $query->where('company_id', $company->id))
            ->first();

        abort_if($application === null, 403, 'Poti evalua doar companii la care ai aplicat.');

        EmployerReview::updateOrCreate(
            ['application_id' => $application->id],
            [
                'company_id' => $company->id,
                'candidate_id' => $request->user()->id,
                'rating' => $request->integer('rating'),
                'would_apply_again' => $request->boolean('would_apply_again'),
                'body' => $request->input('body'),
                'is_verified' => true,
                'status' => 'published',
            ]
        );

        return redirect()
            ->route('companies.show', $company)
            ->with('status', 'Multumim! Evaluarea ta verificata a fost publicata.');
    }
}
