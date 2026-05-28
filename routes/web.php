<?php

use App\Enums\UserRole;
use App\Http\Controllers\Candidate\DashboardController as CandidateDashboardController;
use App\Http\Controllers\Candidate\ProfileController as CandidateProfileController;
use App\Http\Controllers\Employer\CompanyController as EmployerCompanyController;
use App\Http\Controllers\Employer\DashboardController as EmployerDashboardController;
use App\Http\Controllers\Employer\JobController as EmployerJobController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\JobController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/jobs', [JobController::class, 'index'])->name('jobs.index');
Route::get('/companies/{company:slug}/jobs/{job:slug}', [JobController::class, 'show'])
    ->scopeBindings()
    ->name('jobs.show');

Route::get('/dashboard', function () {
    return match (auth()->user()->role) {
        UserRole::Candidate => redirect()->route('candidate.dashboard'),
        UserRole::Employer => redirect()->route('employer.dashboard'),
        UserRole::Admin => redirect()->route('admin.dashboard'),
    };
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('candidate')->name('candidate.')->middleware('role:candidate')->group(function () {
        Route::get('/dashboard', CandidateDashboardController::class)->name('dashboard');
        Route::get('/profile', [CandidateProfileController::class, 'edit'])->name('profile.edit');
        Route::post('/profile', [CandidateProfileController::class, 'update'])->name('profile.update');
        Route::get('/profile/cv', [CandidateProfileController::class, 'downloadCv'])->name('profile.cv');
    });

    Route::prefix('employer')->name('employer.')->middleware('role:employer')->group(function () {
        Route::get('/dashboard', EmployerDashboardController::class)->name('dashboard');
        Route::resource('companies', EmployerCompanyController::class);
        Route::resource('jobs', EmployerJobController::class);
    });

    Route::get('/admin/dashboard', fn () => view('dashboard'))
        ->middleware('role:admin')
        ->name('admin.dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
