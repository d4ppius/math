<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'app:make-admin {email : E-Mail-Adresse eines bestehenden Benutzers} {--revoke : Admin-Rechte entziehen statt vergeben}';

    protected $description = 'Grants (or with --revoke, removes) admin rights for an existing user';

    public function handle(): int
    {
        $user = User::where('email', strtolower($this->argument('email')))->first();

        if (! $user) {
            $this->error('Kein Benutzer mit dieser E-Mail-Adresse gefunden. Bitte zuerst normal registrieren.');

            return self::FAILURE;
        }

        // is_admin is deliberately not mass-assignable.
        $user->is_admin = ! $this->option('revoke');
        $user->save();

        $this->info($user->is_admin
            ? "{$user->email} ist jetzt Administrator."
            : "{$user->email} ist kein Administrator mehr.");

        return self::SUCCESS;
    }
}
