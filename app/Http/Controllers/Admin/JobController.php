<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JobController extends Controller
{
    public function index(): View
    {
        return view('admin.jobs.index', [
            'jobs' => Job::query()
                ->with('company.owner')
                ->withCount('applications')
                ->latest()
                ->paginate(15),
        ]);
    }

    public function update(Request $request, Job $job): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'published', 'closed', 'rejected'])],
        ]);

        $job->status = $validated['status'];

        if ($validated['status'] === 'published' && $job->published_at === null) {
            $job->published_at = now();
        }

        $job->save();

        return redirect()->route('admin.jobs.index')
            ->with('status', 'job-updated');
    }
}
