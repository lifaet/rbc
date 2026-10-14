<?php
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\ExportController;
use App\Livewire\Admin\CycleManager; use App\Livewire\Admin\FactoryManager; use App\Livewire\Admin\QuestionManager; use App\Livewire\Admin\UserManager;
use App\Livewire\Dashboard\ReportDashboard; use App\Livewire\Profile\EditProfile; use App\Livewire\Survey\SurveyForm;
use Illuminate\Support\Facades\Route;
Route::redirect('/', '/login');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/profile', EditProfile::class)->name('profile.edit');
    Route::middleware('role:factory_user')->group(function () {
        Route::get('/survey', SurveyForm::class)->name('survey.index');
    });
    Route::middleware('role:admin,super_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', ReportDashboard::class)->name('dashboard');
        Route::get('/cycles', CycleManager::class)->name('cycles');
        Route::get('/cycles/{cycle}/questions', QuestionManager::class)->name('questions');
        Route::get('/factories', FactoryManager::class)->name('factories');
        Route::get('/users', UserManager::class)->name('users');
        Route::get('/exports/submissions/excel', [ExportController::class, 'submissionsExcel'])->name('exports.submissions.excel');
        Route::get('/exports/factories/{factory}/excel', [ExportController::class, 'factoryExcel'])->name('exports.factory.excel');
        Route::get('/exports/factories/{factory}/pdf', [ExportController::class, 'factoryPdf'])->name('exports.factory.pdf');
    });
});
