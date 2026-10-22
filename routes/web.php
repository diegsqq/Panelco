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
    Route::post('/upload', [App\Http\Controllers\DocumentController::class, 'store'])->name('documents.store');
    Route::get('/library', [App\Http\Controllers\DocumentController::class, 'index'])->name('documents.library');
    Route::get('/library/{document}', [App\Http\Controllers\DocumentController::class, 'show'])->name('documents.show');
    Route::get('/library/{document}/download', [App\Http\Controllers\DocumentController::class, 'download'])->name('documents.download');
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
    Route::put('/settings', [App\Http\Controllers\SettingsController::class, 'update'])->name('settings.update');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserController::class)->except(['show']);
    Route::resource('departments', DepartmentController::class)->except(['show']);
    Route::resource('categories', DocumentCategoryController::class)->except(['show']);
    Route::post('categories/ai-keywords', [DocumentCategoryController::class, 'aiKeywords'])->name('categories.ai-keywords');
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
});
