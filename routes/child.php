<?php

use App\Http\Controllers\Child\ChildAuthController;
use App\Http\Controllers\Child\ChildHomeController;
use App\Http\Controllers\Child\PracticeSessionController;
use Illuminate\Support\Facades\Route;

// Personal magic-link icon each child gets added to the iPad home screen.
// Both verbs target the SAME URL on purpose: that is what Safari saves when
// a parent taps "Add to Home Screen", so it must never redirect elsewhere.
Route::get('/k/{token}', [ChildAuthController::class, 'loginViaToken'])->name('child.magic-link');
Route::post('/k/{token}', [ChildAuthController::class, 'verifyPin'])
    ->middleware('throttle:6,1')
    ->name('child.magic-link.pin');

Route::middleware('auth:child')->group(function () {
    Route::get('/kind', ChildHomeController::class)->name('child.home');
    Route::post('/kind/logout', [ChildAuthController::class, 'logout'])->name('child.logout');

    Route::post('/kind/sessions', [PracticeSessionController::class, 'start'])->name('child.sessions.start');

    Route::middleware('child.owns')->group(function () {
        Route::get('/kind/sessions/{session}', [PracticeSessionController::class, 'show'])->name('child.sessions.show');
        Route::get('/kind/sessions/{session}/next-question', [PracticeSessionController::class, 'nextQuestion'])->name('child.sessions.next-question');
        Route::post('/kind/sessions/{session}/attempts', [PracticeSessionController::class, 'attempt'])->name('child.sessions.attempts');
        Route::post('/kind/sessions/{session}/finish', [PracticeSessionController::class, 'finish'])->name('child.sessions.finish');
        Route::get('/kind/sessions/{session}/summary', [PracticeSessionController::class, 'summary'])->name('child.sessions.summary');
    });
});
