<?php

namespace App\Providers;

use App\Services\ExerciseTypes\ExerciseTypeRegistry;
use App\Services\Tenancy\FamilyContext;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
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
        VerifyEmail::toMailUsing(fn (object $notifiable, string $url) => (new MailMessage)
            ->subject('Bitte bestätige deine E-Mail-Adresse')
            ->greeting("Hallo {$notifiable->name}!")
            ->line('Willkommen bei '.config('app.name').'! Bitte bestätige deine E-Mail-Adresse, damit du dein Konto nutzen kannst.')
            ->action('E-Mail-Adresse bestätigen', $url)
            ->line('Der Link ist '.config('auth.verification.expire', 60).' Minuten gültig. Falls du dich nicht registriert hast, kannst du diese Nachricht ignorieren.')
            ->salutation('Viele Grüsse, dein '.config('app.name')));

        ResetPassword::toMailUsing(fn (object $notifiable, string $token) => (new MailMessage)
            ->subject('Passwort zurücksetzen')
            ->greeting("Hallo {$notifiable->name}!")
            ->line('Für dein Konto wurde ein neues Passwort angefordert.')
            ->action('Neues Passwort festlegen', url(route('password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()], false)))
            ->line('Der Link ist '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' Minuten gültig. Wenn du kein neues Passwort angefordert hast, musst du nichts tun.')
            ->salutation('Viele Grüsse, dein '.config('app.name')));

        // Listeners in app/Listeners are auto-discovered by their handle()
        // type-hint (see `php artisan event:list`) — registering them here
        // too would attach them twice per event.
    }
}
