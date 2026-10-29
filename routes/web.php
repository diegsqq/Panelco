<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\DocumentCategoryController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('home')
        : redirect()->route('login');
});

Auth::routes(['register' => false]);

Route::middleware('auth')->group(function () {
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::get('/dashboard', [App\Http\Controllers\HomeController::class, 'index'])->name('dashboard');

    Route::get('/upload', [App\Http\Controllers\DocumentController::class, 'create'])->name('documents.upload');
    Route::post('/upload/suggest', [App\Http\Controllers\DocumentController::class, 'suggest'])->name('documents.suggest');
    Route::post('/upload', [App\Http\Controllers\DocumentController::class, 'store'])->name('documents.store');
    Route::get('/library', [App\Http\Controllers\DocumentController::class, 'index'])->name('documents.library');
    Route::get('/library/{document}', [App\Http\Controllers\DocumentController::class, 'show'])->name('documents.show');
    Route::get('/library/{document}/download', [App\Http\Controllers\DocumentController::class, 'download'])->name('documents.download');
    Route::get('/library/{document}/view', [App\Http\Controllers\DocumentController::class, 'view'])->name('documents.view');
    Route::get('/library/{document}/thumb', [App\Http\Controllers\DocumentController::class, 'thumb'])->name('documents.thumb');
    Route::post('/library/{document}/retry', [App\Http\Controllers\DocumentController::class, 'retry'])->name('documents.retry');
    Route::post('/library/{document}/approve', [App\Http\Controllers\DocumentController::class, 'approve'])->name('documents.approve');
    Route::delete('/library/{document}', [App\Http\Controllers\DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::get('/queue', [App\Http\Controllers\DocumentController::class, 'queue'])->name('queue.index');

    Route::get('/chat', [App\Http\Controllers\ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat', [App\Http\Controllers\ChatController::class, 'store'])->name('chat.store');
    Route::get('/chat/{conversation}', [App\Http\Controllers\ChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/{conversation}', [App\Http\Controllers\ChatController::class, 'askMessage'])->name('chat.ask');
    Route::delete('/chat/{conversation}', [App\Http\Controllers\ChatController::class, 'destroy'])->name('chat.destroy');
    Route::get('/settings', [App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
    Route::get('/settings/security', [App\Http\Controllers\SettingsController::class, 'security'])->name('settings.security');
    Route::get('/settings/preferences', [App\Http\Controllers\SettingsController::class, 'preferences'])->name('settings.preferences');
    Route::put('/settings/profile', [App\Http\Controllers\SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::put('/settings/profile/email/verify', [App\Http\Controllers\SettingsController::class, 'verifyEmail'])->name('settings.profile.verify-email');
    Route::put('/settings/profile/phone/verify', [App\Http\Controllers\SettingsController::class, 'verifyPhone'])->name('settings.profile.verify-phone');
    Route::post('/settings/profile/resend', [App\Http\Controllers\SettingsController::class, 'resendProfileCode'])->name('settings.profile.resend');
    Route::post('/settings/profile/avatar', [App\Http\Controllers\SettingsController::class, 'updateAvatar'])->name('settings.profile.avatar');
    Route::delete('/settings/profile/avatar', [App\Http\Controllers\SettingsController::class, 'destroyAvatar'])->name('settings.profile.avatar.destroy');
    Route::get('/profile/avatar', [App\Http\Controllers\SettingsController::class, 'showAvatar'])->name('profile.avatar');
    Route::put('/settings/password', [App\Http\Controllers\SettingsController::class, 'updatePassword'])->name('settings.password');
    Route::put('/settings/retention', [App\Http\Controllers\SettingsController::class, 'updateRetention'])->name('settings.retention');
    Route::post('/settings/2fa/setup', [App\Http\Controllers\SettingsController::class, 'twoFactorSetup'])->name('settings.2fa.setup');
    Route::post('/settings/2fa/confirm', [App\Http\Controllers\SettingsController::class, 'twoFactorConfirm'])->name('settings.2fa.confirm');
    Route::post('/settings/2fa/disable', [App\Http\Controllers\SettingsController::class, 'twoFactorDisable'])->name('settings.2fa.disable');
    Route::post('/settings/theme', [App\Http\Controllers\SettingsController::class, 'updateTheme'])->name('settings.theme');
    Route::post('/settings/locale', [App\Http\Controllers\SettingsController::class, 'updateLocale'])->name('settings.locale');
    Route::post('/settings/notifications', [App\Http\Controllers\SettingsController::class, 'updateNotifications'])->name('settings.notifications');
    Route::put('/settings', [App\Http\Controllers\SettingsController::class, 'update'])->name('settings.update');
});

// Second login step when 2FA is enabled (guest; password already checked).
Route::get('/two-factor', [App\Http\Controllers\TwoFactorController::class, 'show'])->name('two-factor.show');
Route::post('/two-factor', [App\Http\Controllers\TwoFactorController::class, 'verify'])->name('two-factor.verify');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserController::class)->except(['show']);
    Route::resource('departments', DepartmentController::class)->except(['show']);
    Route::resource('categories', DocumentCategoryController::class)->except(['show']);
    Route::post('categories/ai-keywords', [DocumentCategoryController::class, 'aiKeywords'])->name('categories.ai-keywords');
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
});
