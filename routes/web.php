<?php

use App\Http\Controllers\ChildIconController;
use App\Http\Controllers\ChildManifestController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\LegalController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('home');

Route::get('/impressum', [LegalController::class, 'imprint'])->name('legal.imprint');
Route::get('/datenschutz', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/kontakt', [ContactController::class, 'show'])->name('contact.show');
Route::post('/kontakt', [ContactController::class, 'store'])->middleware('throttle:30,1')->name('contact.store');

// Deliberately not /icons/...: that path is a decades-old default Apache
// alias (Alias /icons/ "/usr/share/apache2/icons/", still enabled on most
// stock Debian/Ubuntu installs) that serves Apache's own directory-listing
// icons. It's resolved before the request ever reaches PHP/.htaccess, so a
// route living under /icons/ 404s on such a server no matter how correctly
// Laravel itself is configured — confirmed on the production server here.
Route::get('/child-icon/{child}/{size}.png', [ChildIconController::class, 'show'])->name('child.icon');
Route::get('/manifest/kind/{token}.webmanifest', [ChildManifestController::class, 'show'])->name('child.manifest');

require __DIR__.'/parent.php';
require __DIR__.'/admin.php';
require __DIR__.'/child.php';
