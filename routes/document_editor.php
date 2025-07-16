<?php

use Illuminate\Support\Facades\Route;
use Misusonu18\DocumentEditor\Http\Controllers\DocumentEditorController;

Route::controller(DocumentEditorController::class)->group(function () {
    Route::get('/docs', 'index')->name('index');
    Route::get('/docs-edit', 'edit')->name('edit');
    Route::post('/docs-update', 'update')->name('update');
});
