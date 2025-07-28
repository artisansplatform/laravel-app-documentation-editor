<?php

use Artisansplatform\LaravelAppDocumentationEditor\Http\Controllers\LaravelAppDocumentationEditorController;
use Illuminate\Support\Facades\Route;

Route::controller(LaravelAppDocumentationEditorController::class)->group(function (): void {
    Route::get('/documentation', 'index')->name('index');
    Route::get('/documentation/edit', 'edit')->name('edit');
    Route::post('/documentation/update', 'update')->name('update');
});
