<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

use App\Http\Controllers\DocumentController;

Route::get('/home', [DocumentController::class, 'index'])->name('home');
Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
Route::get('/documents', fn() => redirect()->route('home'));
Route::get('/documents/{document}/review', [DocumentController::class, 'show'])->name('documents.show');
Route::get('/documents/{document}/file', [DocumentController::class, 'file'])->name('documents.file');
Route::put('/documents/{document}', [DocumentController::class, 'update'])->name('documents.update')->middleware('role:Reviewer');
Route::get('/documents/export', [DocumentController::class, 'export'])->name('documents.export')->middleware('role:Reviewer');
Route::get('/documents/{document}/export', [DocumentController::class, 'exportSingle'])->name('documents.export.single')->middleware('role:Reviewer');
Route::get('/documents/{document}/status', [DocumentController::class, 'status'])->name('documents.status');
Route::post('/documents/{document}/cancel', [DocumentController::class, 'cancel'])->name('documents.cancel');
