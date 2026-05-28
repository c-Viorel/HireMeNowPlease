<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $featuredJobs = Job::query()
            ->with('company')
            ->publiclyVisible()
            ->latest('published_at')
            ->latest('id')
            ->limit(6)
            ->get();

        return view('public.home', [
            'featuredJobs' => $featuredJobs,
        ]);
    }
}
