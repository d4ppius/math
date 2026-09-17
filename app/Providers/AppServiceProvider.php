<?php

namespace App\Providers;

use App\Services\ExerciseTypes\ExerciseTypeRegistry;
use App\Services\Tenancy\FamilyContext;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FamilyContext::class);
        $this->app->singleton(ExerciseTypeRegistry::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Listeners in app/Listeners are auto-discovered by their handle()
        // type-hint (see `php artisan event:list`) — registering them here
        // too would attach them twice per event.
    }
}
