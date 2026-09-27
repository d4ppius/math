<?php

namespace App\Services;

use App\Events\PracticeSessionCompleted;
use App\Models\PracticeSession;

/**
 * Marks a still-active session as completed and, unless it's a preview
 * (ChildPreviewController), dispatches the event that triggers badges and
 * the daily-goal check. Shared by PracticeSessionController (the child
 * finishes on their own, or the time simply runs out on their next request)
 * and the scheduled sessions:close-expired command (nobody comes back at
 * all), so both paths stay in exact lockstep.
 */
class PracticeSessionCompleter
{
    public function complete(PracticeSession $session): void
    {
        if ($session->status !== 'active') {
            return;
        }

        $session->update([
            'status' => 'completed',
            'ended_at' => now(),
            'current_fact_id' => null,
            'current_question_issued_at' => null,
        ]);

        if (! $session->is_preview) {
            PracticeSessionCompleted::dispatch($session);
        }
    }
}
