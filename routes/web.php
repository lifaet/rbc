<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Livewire\Admin\CycleManager;
use App\Livewire\Admin\FactoryManager;
use App\Livewire\Admin\QuestionManager;
use App\Livewire\Dashboard\ReportDashboard;
use App\Livewire\Profile\EditProfile;
use App\Livewire\Survey\SurveyForm;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

// --- Guest routes ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // --- Shared: available to any authenticated user (admin or factory) ---
    Route::get('/profile', EditProfile::class)->name('profile.edit');

    // --- Factory user routes ---
    Route::middleware('role:factory_user')->group(function () {
        Route::get('/survey', SurveyForm::class)->name('survey.index');
    });

    // --- Admin routes ---
    Route::middleware('role:admin,super_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', ReportDashboard::class)->name('dashboard');
        Route::get('/cycles', CycleManager::class)->name('cycles');
        Route::get('/cycles/{cycle}/questions', QuestionManager::class)->name('questions');
        Route::get('/factories', FactoryManager::class)->name('factories');
    });
});
