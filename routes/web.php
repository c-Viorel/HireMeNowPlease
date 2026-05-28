<?php

use App\Enums\UserRole;
use App\Http\Controllers\Admin\CompanyController as AdminCompanyController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\JobController as AdminJobController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Candidate\ApplicationController as CandidateApplicationController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\Candidate\DashboardController as CandidateDashboardController;
use App\Http\Controllers\Candidate\ProfileController as CandidateProfileController;
use App\Http\Controllers\Employer\ApplicationController as EmployerApplicationController;
use App\Http\Controllers\Employer\CompanyController as EmployerCompanyController;
use App\Http\Controllers\Employer\DashboardController as EmployerDashboardController;
use App\Http\Controllers\Employer\JobController as EmployerJobController;
use App\Http\Controllers\Employer\ShortlistController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\JobController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/jobs', [JobController::class, 'index'])->name('jobs.index');
Route::get('/companies/{company:slug}/jobs/{job:slug}', [JobController::class, 'show'])
    ->scopeBindings()
    ->name('jobs.show');
Route::post('/companies/{company:slug}/jobs/{job:slug}/apply', [CandidateApplicationController::class, 'store'])
    ->middleware(['auth', 'verified', 'role:candidate'])
    ->scopeBindings()
    ->name('jobs.apply');

Route::get('/dashboard', function () {
    return match (auth()->user()->role) {
        UserRole::Candidate => redirect()->route('candidate.dashboard'),
        UserRole::Employer => redirect()->route('employer.dashboard'),
        UserRole::Admin => redirect()->route('admin.dashboard'),
    };
})->middleware(['auth', 'active', 'verified'])->name('dashboard');

Route::middleware(['auth', 'active', 'verified'])->group(function () {
    Route::post('/applications/{application}/conversations', [ConversationController::class, 'store'])->name('conversations.store');
    Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
    Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store'])->name('messages.store');

    Route::prefix('candidate')->name('candidate.')->middleware('role:candidate')->group(function () {
        Route::get('/dashboard', CandidateDashboardController::class)->name('dashboard');
        Route::get('/applications', [CandidateApplicationController::class, 'index'])->name('applications.index');
        Route::get('/profile', [CandidateProfileController::class, 'edit'])->name('profile.edit');
        Route::post('/profile', [CandidateProfileController::class, 'update'])->name('profile.update');
        Route::get('/profile/cv', [CandidateProfileController::class, 'downloadCv'])->name('profile.cv');
    });

    Route::prefix('employer')->name('employer.')->middleware('role:employer')->group(function () {
        Route::get('/dashboard', EmployerDashboardController::class)->name('dashboard');
        Route::get('/applications', [EmployerApplicationController::class, 'index'])->name('applications.index');
        Route::get('/applications/{application}', [EmployerApplicationController::class, 'show'])->name('applications.show');
        Route::get('/applications/{application}/cv', [EmployerApplicationController::class, 'downloadCv'])->name('applications.cv');
        Route::patch('/applications/{application}/status', [EmployerApplicationController::class, 'updateStatus'])->name('applications.status');
        Route::post('/applications/{application}/shortlist', [ShortlistController::class, 'store'])->name('applications.shortlist');
        Route::resource('companies', EmployerCompanyController::class);
        Route::resource('jobs', EmployerJobController::class);
    });

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::get('/companies', [AdminCompanyController::class, 'index'])->name('companies.index');
        Route::patch('/companies/{company}', [AdminCompanyController::class, 'update'])->name('companies.update');
        Route::get('/jobs', [AdminJobController::class, 'index'])->name('jobs.index');
        Route::patch('/jobs/{job}', [AdminJobController::class, 'update'])->name('jobs.update');
    });
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
