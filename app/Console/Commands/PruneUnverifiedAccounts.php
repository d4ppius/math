<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneUnverifiedAccounts extends Command
{
    protected $signature = 'accounts:prune-unverified';

    protected $description = 'Delete parent accounts whose e-mail address was never verified (and families left empty by that)';

    public function handle(): int
    {
        $days = (int) config('auth.unverified_retention_days');

        $stale = User::query()
            ->whereNull('email_verified_at')
            ->where('is_admin', false)
            ->where('created_at', '<', now()->subDays($days))
            ->get();

        foreach ($stale as $user) {
            DB::transaction(function () use ($user) {
                $family = $user->family;

                $user->delete();

                if ($family && ! $family->users()->exists()) {
                    $family->delete();
                }
            });
        }

        $this->info("Deleted {$stale->count()} unverified account(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
