<?php

use App\Http\Controllers\Parent\ChildController;
use App\Http\Controllers\Parent\DashboardController;
use App\Http\Controllers\Parent\ExerciseSettingController;
use App\Http\Controllers\Parent\PushSubscriptionController;
use App\Http\Controllers\Parent\StatisticsController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Parent Routes
|--------------------------------------------------------------------------
|
| Deliberately not linked from the child-facing UI. Everything here lives
| under /eltern so the parent area stays out of a curious kid's way.
|
*/

Route::prefix('eltern')->group(function () {
    require __DIR__.'/auth.php';

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        Route::resource('kinder', ChildController::class)
            ->except(['index', 'show'])
            ->parameters(['kinder' => 'child'])
            ->names('parent.children');

        Route::post('/kinder/{child}/token', [ChildController::class, 'regenerateToken'])
            ->name('parent.children.token');

        Route::get('/kinder/{child}/uebung', [ExerciseSettingController::class, 'edit'])
            ->name('parent.children.exercise-settings.edit');
        Route::put('/kinder/{child}/uebung', [ExerciseSettingController::class, 'update'])
            ->name('parent.children.exercise-settings.update');

        Route::get('/kinder/{child}/statistik', [StatisticsController::class, 'show'])
            ->name('parent.children.statistics');

        Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])
            ->name('parent.push-subscriptions.store');
        Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy'])
            ->name('parent.push-subscriptions.destroy');
    });
});
