<?php

use Illuminate\Support\Facades\Route;
use Misusonu18\DocumentEditor\Http\Controllers\DocumentEditorController;

Route::controller(DocumentEditorController::class)->group(function (): void {
    Route::get('/documentation', 'index')->name('index');
    Route::get('/documentation/edit', 'edit')->name('edit');
    Route::post('/documentation/update', 'update')->name('update');
});
