<?php

use App\Http\Controllers\ChildIconController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/icons/child/{child}/{size}.png', [ChildIconController::class, 'show'])->name('child.icon');

require __DIR__.'/parent.php';
require __DIR__.'/child.php';
