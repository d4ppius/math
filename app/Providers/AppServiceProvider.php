<?php

namespace App\Providers;

use App\Events\PracticeSessionCompleted;
use App\Listeners\EvaluateDailyGoal;
use App\Services\ExerciseTypes\ExerciseTypeRegistry;
use App\Services\Tenancy\FamilyContext;
use Illuminate\Support\Facades\Event;
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
        Event::listen(PracticeSessionCompleted::class, EvaluateDailyGoal::class);
    }
}
