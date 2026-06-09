<?php

namespace App\Http\Controllers\Public;

use App\Enums\WorkplaceType;
use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DiasporaController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $jobs = Job::query()
            ->with('company')
            ->publiclyVisible()
            ->where(function ($query): void {
                $query
                    ->where('offers_relocation', true)
                    ->orWhere('workplace_type', WorkplaceType::Remote->value);
            })
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('title', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            })
            ->latest('published_at')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('public.diaspora.index', [
            'jobs' => $jobs,
            'filters' => $filters,
        ]);
    }
}
