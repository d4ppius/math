<?php

use App\Http\Controllers\ChildIconController;
use App\Http\Controllers\ChildManifestController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\LegalController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/impressum', [LegalController::class, 'imprint'])->name('legal.imprint');
Route::get('/datenschutz', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/kontakt', [ContactController::class, 'show'])->name('contact.show');
Route::post('/kontakt', [ContactController::class, 'store'])->middleware('throttle:30,1')->name('contact.store');

Route::get('/icons/child/{child}/{size}.png', [ChildIconController::class, 'show'])->name('child.icon');
Route::get('/manifest/kind/{token}.webmanifest', [ChildManifestController::class, 'show'])->name('child.manifest');

require __DIR__.'/parent.php';
require __DIR__.'/admin.php';
require __DIR__.'/child.php';
