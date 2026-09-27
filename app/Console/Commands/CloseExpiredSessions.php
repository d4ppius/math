<?php

namespace App\Console\Commands;

use App\Models\PracticeSession;
use App\Services\PracticeSessionCompleter;
use Illuminate\Console\Command;

/**
 * Closes sessions nobody ever came back to after their time ran out — e.g. a
 * child closes the app mid-session and never opens it again that day.
 * Without this, such a session stays "active" forever (PracticeSession's
 * status only ever advances on the next request touching it) and never
 * triggers the daily-goal check or its parent notification, even if the
 * child answered plenty of questions before leaving.
 */
class CloseExpiredSessions extends Command
{
    protected $signature = 'sessions:close-expired';

    protected $description = 'Complete practice sessions whose time ran out but nobody came back to finish';

    public function handle(PracticeSessionCompleter $completer): int
    {
        $sessions = PracticeSession::where('status', 'active')->get()
            ->filter(fn (PracticeSession $session) => ! $session->hasTimeRemaining());

        foreach ($sessions as $session) {
            $completer->complete($session);
        }

        $this->info("Closed {$sessions->count()} expired session(s).");

        return self::SUCCESS;
    }
}
