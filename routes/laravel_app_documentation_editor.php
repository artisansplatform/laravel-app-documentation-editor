<?php

use Artisansplatform\LaravelAppDocumentationEditor\Http\Controllers\LaravelAppDocumentationEditorController;
use Illuminate\Support\Facades\Route;

Route::controller(LaravelAppDocumentationEditorController::class)->group(function (): void {
    Route::get('/assets/app.js', 'serveJs')->name('assets.js');
    Route::get('/assets/app.css', 'serveCss')->name('assets.css');
    Route::get('/documentation', 'index')->name('index');
    Route::get('/documentation/edit', 'edit')->name('edit');
    Route::post('/documentation/update', 'update')->name('update');
});
