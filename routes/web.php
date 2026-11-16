<?php

use App\Http\Controllers\Admin\FormController;
use App\Http\Controllers\Admin\ResultController;
use App\Http\Controllers\Admin\UploadController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PublicFormController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(Auth::check() ? 'admin.forms.index' : 'login'));

// ---- Borang awam (untuk responden) ----
Route::get('/f/{slug}', [PublicFormController::class, 'show'])->name('form.show');
Route::post('/f/{slug}', [PublicFormController::class, 'submit'])->middleware('throttle:20,1')->name('form.submit');
Route::get('/f/{slug}/terima-kasih', [PublicFormController::class, 'thanks'])->name('form.thanks');

// ---- Log masuk admin ----
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ---- Panel admin ----
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [FormController::class, 'index'])->name('forms.index');
    Route::get('/forms/create', [FormController::class, 'create'])->name('forms.create');
    Route::post('/forms', [FormController::class, 'store'])->name('forms.store');
    Route::get('/forms/{form}/edit', [FormController::class, 'edit'])->name('forms.edit');
    Route::put('/forms/{form}', [FormController::class, 'update'])->name('forms.update');
    Route::delete('/forms/{form}', [FormController::class, 'destroy'])->name('forms.destroy');
    Route::post('/forms/{form}/duplicate', [FormController::class, 'duplicate'])->name('forms.duplicate');
    Route::post('/forms/{form}/status', [FormController::class, 'setStatus'])->name('forms.status');

    Route::get('/forms/{form}/results', [ResultController::class, 'summary'])->name('forms.results');
    Route::get('/forms/{form}/responses', [ResultController::class, 'responses'])->name('forms.responses');
    Route::delete('/forms/{form}/test-responses', [ResultController::class, 'destroyTests'])->name('forms.tests.destroy');
    Route::get('/forms/{form}/responses/{response}', [ResultController::class, 'show'])->name('forms.response');
    Route::delete('/forms/{form}/responses/{response}', [ResultController::class, 'destroy'])->name('forms.response.destroy');
    Route::get('/forms/{form}/export', [ResultController::class, 'export'])->name('forms.export');

    Route::post('/upload', [UploadController::class, 'store'])->name('upload');
});
