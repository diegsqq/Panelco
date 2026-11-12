<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\DocumentCategoryController;
use App\Http\Controllers\Admin\DocumentRetentionController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes(['register' => false, 'reset' => false]);

Route::middleware('auth')->group(function () {
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::get('/dashboard', [App\Http\Controllers\HomeController::class, 'index'])->name('dashboard');
    Route::view('/help', 'help.index')->name('help');

    Route::get('/upload', [App\Http\Controllers\DocumentController::class, 'create'])->name('documents.upload');
    Route::post('/upload/suggest', [App\Http\Controllers\DocumentController::class, 'suggest'])->name('documents.suggest');
    Route::post('/upload', [App\Http\Controllers\DocumentController::class, 'store'])->name('documents.store');
    Route::get('/library', [App\Http\Controllers\DocumentController::class, 'index'])->name('documents.library');
    Route::get('/library/export', [App\Http\Controllers\DocumentController::class, 'export'])->name('documents.export');
    Route::get('/library/{document}/edit', [App\Http\Controllers\DocumentController::class, 'edit'])->name('documents.edit');
    Route::get('/library/{document}/content/edit', [App\Http\Controllers\DocumentController::class, 'editContent'])->name('documents.edit-content');
    Route::put('/library/{document}/content', [App\Http\Controllers\DocumentController::class, 'updateContent'])->name('documents.update-content');
    Route::post('/library/{document}/content/images', [App\Http\Controllers\DocumentController::class, 'storeContentImage'])->name('documents.content-images');
    Route::get('/library/{document}/content/images/{filename}', [App\Http\Controllers\DocumentController::class, 'contentImage'])->where('filename', '[A-Za-z0-9._-]+')->name('documents.content-image');
Route::get('/library/{document}/content/docx', [App\Http\Controllers\DocumentController::class, 'exportContentDocx'])->name('documents.content-docx');
Route::get('/library/{document}/content/pdf', [App\Http\Controllers\DocumentController::class, 'exportContentPdf'])->name('documents.content-pdf');
Route::post('/library/{document}/content/pdf/selection', [App\Http\Controllers\DocumentController::class, 'exportContentSelectionPdf'])->name('documents.content-pdf-selection');
Route::get('/library/{document}/print', [App\Http\Controllers\DocumentController::class, 'printPage'])->name('documents.print');
    Route::put('/library/{document}', [App\Http\Controllers\DocumentController::class, 'update'])->name('documents.update');
    Route::get('/library/{document}/versions/{version}/download', [App\Http\Controllers\DocumentController::class, 'downloadVersion'])->name('documents.versions.download');
Route::post('/library/{document}/versions/{version}/restore', [App\Http\Controllers\DocumentController::class, 'restoreVersion'])->name('documents.versions.restore');
Route::post('/library/{document}/share', [App\Http\Controllers\DocumentController::class, 'shareDocument'])->name('documents.share');
Route::post('/library/{document}/favorite', [App\Http\Controllers\DocumentController::class, 'toggleFavorite'])->name('documents.favorite');
Route::post('/library/{document}/tags', [App\Http\Controllers\DocumentController::class, 'updateDocumentTags'])->name('documents.tags');
Route::get('/storage-usage', [App\Http\Controllers\DocumentController::class, 'storageUsage'])->name('documents.storage-usage');
    Route::get('/library/{document}', [App\Http\Controllers\DocumentController::class, 'show'])->name('documents.show');
    Route::get('/library/{document}/download', [App\Http\Controllers\DocumentController::class, 'download'])->name('documents.download');
    Route::get('/library/{document}/view', [App\Http\Controllers\DocumentController::class, 'view'])->name('documents.view');
Route::get('/library/{document}/pdf', [App\Http\Controllers\DocumentController::class, 'toPdf'])->name('documents.pdf');
    Route::get('/library/{document}/thumb', [App\Http\Controllers\DocumentController::class, 'thumb'])->name('documents.thumb');
    Route::post('/library/{document}/retry', [App\Http\Controllers\DocumentController::class, 'retry'])->name('documents.retry');
    Route::post('/library/{document}/ask', [App\Http\Controllers\ChatController::class, 'askDocument'])->name('documents.ask');
    Route::post('/library/{document}/approve', [App\Http\Controllers\DocumentController::class, 'approve'])->name('documents.approve');
    Route::post('/library/{document}/reject-classification', [App\Http\Controllers\DocumentController::class, 'rejectClassification'])->name('documents.reject-classification');
    Route::get('/approvals', [App\Http\Controllers\DocumentController::class, 'approvals'])->name('documents.approvals');
    Route::get('/classification-reviews', [App\Http\Controllers\DocumentController::class, 'classificationReviews'])->name('documents.classification-reviews');
    Route::post('/library/{document}/approve-document', [App\Http\Controllers\DocumentController::class, 'approveDocument'])->name('documents.approve-document');
    Route::post('/library/{document}/reject-document', [App\Http\Controllers\DocumentController::class, 'rejectDocument'])->name('documents.reject-document');
Route::post('/library/{document}/workflow/submit', [App\Http\Controllers\DocumentController::class, 'submitDocument'])->name('documents.workflow-submit');
Route::post('/library/{document}/workflow/review', [App\Http\Controllers\DocumentController::class, 'startDocumentReview'])->name('documents.workflow-review');
Route::post('/library/{document}/workflow/approve', [App\Http\Controllers\DocumentController::class, 'approveWorkflow'])->name('documents.workflow-approve');
Route::post('/library/{document}/workflow/reject', [App\Http\Controllers\DocumentController::class, 'rejectWorkflow'])->name('documents.workflow-reject');
Route::post('/library/{document}/workflow/archive', [App\Http\Controllers\DocumentController::class, 'archiveDocument'])->name('documents.workflow-archive');
Route::post('/library/{document}/workflow/assign', [App\Http\Controllers\DocumentController::class, 'assignDocument'])->name('documents.workflow-assign');
Route::post('/library/{document}/workflow/comments', [App\Http\Controllers\DocumentController::class, 'addDocumentComment'])->name('documents.workflow-comment');
    Route::get('/library/{document}/clone', [App\Http\Controllers\DocumentController::class, 'clone'])->name('documents.clone');
    Route::delete('/library/{document}', [App\Http\Controllers\DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::get('/trash', [App\Http\Controllers\DocumentController::class, 'trash'])->name('documents.trash');
    Route::post('/trash/{document}/restore', [App\Http\Controllers\DocumentController::class, 'restore'])->withTrashed()->name('documents.restore');
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

Route::middleware(['auth'])->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [App\Http\Controllers\NotificationController::class, 'index'])->name('index');
    Route::post('{notification}/read', [App\Http\Controllers\NotificationController::class, 'read'])->name('read');
    Route::post('read-all', [App\Http\Controllers\NotificationController::class, 'readAll'])->name('read-all');
});

// Second login step when 2FA is enabled (guest; password already checked).
Route::get('/two-factor', [App\Http\Controllers\TwoFactorController::class, 'show'])->name('two-factor.show');
Route::post('/two-factor', [App\Http\Controllers\TwoFactorController::class, 'verify'])->middleware('throttle:5,1')->name('two-factor.verify');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('documents/retention-review', [DocumentRetentionController::class, 'index'])->name('documents.retention-review');
    Route::post('users/{user}/access-reviews', [UserController::class, 'recordAccessReview'])->name('users.access-reviews.store');
    Route::patch('users/{user}/status', [UserController::class, 'updateStatus'])->name('users.status');
    Route::post('users/{user}/two-factor/reset', [UserController::class, 'resetTwoFactor'])->name('users.two-factor.reset');
    Route::resource('users', UserController::class)->except(['show']);
    Route::resource('departments', DepartmentController::class)->except(['show']);
    Route::resource('categories', DocumentCategoryController::class)->except(['show']);
    Route::post('categories/ai-keywords', [DocumentCategoryController::class, 'aiKeywords'])->name('categories.ai-keywords');
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('system-health', [SystemHealthController::class, 'index'])->name('system-health.index');
});
