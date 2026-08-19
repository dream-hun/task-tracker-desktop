<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentConversionController;
use App\Http\Controllers\DocumentPdfController;
use App\Http\Controllers\DocumentPrintController;
use App\Http\Controllers\DocumentStatusController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskStatusController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('welcome'))->name('home');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('tasks', TaskController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::patch('tasks/{task}/status', TaskStatusController::class)->name('tasks.status.update');

    Route::resource('documents', DocumentController::class)->except(['show']);
    Route::get('documents/{document}/print', DocumentPrintController::class)->name('documents.print');
    Route::get('documents/{document}/pdf', DocumentPdfController::class)->name('documents.pdf');
    Route::patch('documents/{document}/status', DocumentStatusController::class)->name('documents.status.update');
    Route::post('documents/{document}/convert', DocumentConversionController::class)->name('documents.convert');
});

require __DIR__.'/settings.php';
