<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyRequest;
use App\Models\Company;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        return view('employer.companies.index', [
            'companies' => $request->user()->companies()->withCount('jobs')->latest()->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('employer.companies.create');
    }

    public function store(CompanyRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $company = Company::create([
            'owner_id' => $request->user()->id,
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['name']),
            'description' => $validated['description'] ?? null,
            'website' => $validated['website'] ?? null,
            'location' => $validated['location'] ?? null,
            'status' => 'pending',
        ]);

        if ($request->hasFile('logo')) {
            $company->update([
                'logo_path' => $request->file('logo')->store("company-logos/{$company->id}", 'public'),
            ]);
        }

        return redirect()->route('employer.companies.index')
            ->with('status', 'company-created');
    }

    public function show(Company $company): View
    {
        $this->authorizeOwner($company);

        return view('employer.companies.show', [
            'company' => $company->loadCount('jobs')->load(['jobs' => fn ($query) => $query->latest()->take(10)]),
        ]);
    }

    public function edit(Company $company): View
    {
        $this->authorizeOwner($company);

        return view('employer.companies.edit', [
            'company' => $company,
        ]);
    }

    public function update(CompanyRequest $request, Company $company): RedirectResponse
    {
        $this->authorizeOwner($company);

        $validated = $request->validated();
        $data = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'website' => $validated['website'] ?? null,
            'location' => $validated['location'] ?? null,
        ];

        if ($company->name !== $validated['name']) {
            $data['slug'] = $this->uniqueSlug($validated['name'], $company);
        }

        if ($request->hasFile('logo')) {
            if ($company->logo_path && Storage::disk('public')->exists($company->logo_path)) {
                Storage::disk('public')->delete($company->logo_path);
            }

            $data['logo_path'] = $request->file('logo')->store("company-logos/{$company->id}", 'public');
        }

        $company->update($data);

        return redirect()->route('employer.companies.index')
            ->with('status', 'company-updated');
    }

    public function destroy(Company $company): RedirectResponse
    {
        $this->authorizeOwner($company);

        if ($company->logo_path && Storage::disk('public')->exists($company->logo_path)) {
            Storage::disk('public')->delete($company->logo_path);
        }

        $company->delete();

        return redirect()->route('employer.companies.index')
            ->with('status', 'company-deleted');
    }

    private function authorizeOwner(Company $company): void
    {
        abort_unless($company->owner_id === auth()->id(), 403);
    }

    private function uniqueSlug(string $name, ?Company $ignore = null): string
    {
        $base = Str::slug($name) ?: 'company';
        $slug = $base;
        $counter = 2;

        while (Company::query()
            ->where('slug', $slug)
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
