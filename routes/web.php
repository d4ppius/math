<?php

use App\Http\Controllers\ChildIconController;
use App\Http\Controllers\ChildManifestController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/icons/child/{child}/{size}.png', [ChildIconController::class, 'show'])->name('child.icon');
Route::get('/manifest/kind/{token}.webmanifest', [ChildManifestController::class, 'show'])->name('child.manifest');

require __DIR__.'/parent.php';
require __DIR__.'/child.php';
