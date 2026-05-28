<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyRequest;
use App\Models\Company;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    private const SLUG_RETRY_ATTEMPTS = 3;

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

        $company = $this->withUniqueSlugRetry(
            $validated['name'],
            null,
            fn (string $slug) => Company::create([
                'owner_id' => $request->user()->id,
                'name' => $validated['name'],
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
                'website' => $validated['website'] ?? null,
                'location' => $validated['location'] ?? null,
                'status' => 'pending',
            ])
        );

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

        if ($request->hasFile('logo')) {
            if ($company->logo_path && Storage::disk('public')->exists($company->logo_path)) {
                Storage::disk('public')->delete($company->logo_path);
            }

            $data['logo_path'] = $request->file('logo')->store("company-logos/{$company->id}", 'public');
        }

        if ($company->name !== $validated['name']) {
            $this->withUniqueSlugRetry(
                $validated['name'],
                $company,
                function (string $slug) use ($company, $data): Company {
                    $company->update([...$data, 'slug' => $slug]);

                    return $company;
                }
            );
        } else {
            $company->update($data);
        }

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

    private function uniqueSlug(string $name, ?Company $ignore = null, bool $freshSuffix = false): string
    {
        $base = Str::slug($name) ?: 'company';
        $slug = $freshSuffix ? $base.'-'.Str::lower(Str::random(8)) : $base;
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

    /**
     * @template TReturn
     *
     * @param callable(string): TReturn $operation
     * @return TReturn
     */
    private function withUniqueSlugRetry(string $name, ?Company $ignore, callable $operation): mixed
    {
        for ($attempt = 0; $attempt < self::SLUG_RETRY_ATTEMPTS; $attempt++) {
            try {
                return $operation($this->uniqueSlug($name, $ignore, $attempt > 0));
            } catch (QueryException $exception) {
                if (! $this->isUniqueConstraintViolation($exception) || $attempt === self::SLUG_RETRY_ATTEMPTS - 1) {
                    throw $exception;
                }
            }
        }
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        $driverCode = (string) ($exception->errorInfo[1] ?? '');
        $message = Str::lower($exception->getMessage());

        return in_array($sqlState, ['23000', '23505'], true)
            || in_array($driverCode, ['1062', '1555', '2067'], true)
            || str_contains($message, 'unique constraint')
            || str_contains($message, 'duplicate entry');
    }
}
