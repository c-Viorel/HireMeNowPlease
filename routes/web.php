<?php

use App\Enums\UserRole;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\JobController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/jobs', [JobController::class, 'index'])->name('jobs.index');
Route::get('/jobs/{job:slug}', [JobController::class, 'show'])->name('jobs.show');

Route::get('/dashboard', function () {
    return match (auth()->user()->role) {
        UserRole::Candidate => redirect()->route('candidate.dashboard'),
        UserRole::Employer => redirect()->route('employer.dashboard'),
        UserRole::Admin => redirect()->route('admin.dashboard'),
    };
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/candidate/dashboard', fn () => view('dashboard'))
        ->middleware('role:candidate')
        ->name('candidate.dashboard');

    Route::get('/employer/dashboard', fn () => view('dashboard'))
        ->middleware('role:employer')
        ->name('employer.dashboard');

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
