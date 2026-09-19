<?php

namespace App\Console\Commands;

use App\Models\ContactMessage;
use Illuminate\Console\Command;

class PruneContactMessages extends Command
{
    protected $signature = 'contact:prune';

    protected $description = 'Delete handled contact messages older than the retention period';

    public function handle(): int
    {
        $months = (int) config('contact.retention_months');

        $deleted = ContactMessage::query()
            ->whereNotNull('handled_at')
            ->where('handled_at', '<', now()->subMonths($months))
            ->delete();

        $this->info("Deleted {$deleted} handled contact message(s) older than {$months} months.");

        return self::SUCCESS;
    }
}
