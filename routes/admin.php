<?php

use App\Http\Controllers\Admin\ChildController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExerciseTypeController;
use App\Http\Controllers\Admin\FactController;
use App\Http\Controllers\Admin\FamilyController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Cross-family administration. Access is limited to users with is_admin,
| which can only be granted via `php artisan app:make-admin`.
|
*/

Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/familien', [FamilyController::class, 'index'])->name('families.index');
    Route::get('/familien/{family}', [FamilyController::class, 'show'])->name('families.show');
    Route::put('/familien/{family}', [FamilyController::class, 'update'])->name('families.update');
    Route::delete('/familien/{family}', [FamilyController::class, 'destroy'])->name('families.destroy');

    Route::delete('/benutzer/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/benutzer/{user}/impersonate', [ImpersonationController::class, 'start'])->name('users.impersonate');

    // Children are edited through the regular parent screens (ChildPolicy
    // lets admins through); this is only the cross-family overview.
    Route::get('/kinder', [ChildController::class, 'index'])->name('children.index');

    Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
    Route::get('/sessions/{session}', [SessionController::class, 'show'])->name('sessions.show');

    Route::get('/uebungen', [ExerciseTypeController::class, 'index'])->name('exercises.index');
    Route::patch('/uebungen/{exerciseType}', [ExerciseTypeController::class, 'update'])->name('exercises.update');
    Route::get('/uebungen/{exerciseType}/aufgaben', [FactController::class, 'index'])->name('exercises.facts.index');

    Route::get('/aufgaben/{fact}/bearbeiten', [FactController::class, 'edit'])->name('facts.edit');
    Route::put('/aufgaben/{fact}', [FactController::class, 'update'])->name('facts.update');
});

// Outside the admin group on purpose: while impersonating, the current user
// is the (non-admin) parent, so the admin middleware would reject this.
Route::post('/impersonation/stop', [ImpersonationController::class, 'stop'])
    ->middleware('auth')
    ->name('impersonation.stop');
