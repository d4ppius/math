<?php

namespace App\Listeners;

use App\Events\PracticeSessionCompleted;
use App\Models\DailyGoalLog;
use App\Notifications\DailyGoalAchieved;
use Illuminate\Support\Facades\Notification;

class EvaluateDailyGoal
{
    public const DAILY_GOAL_QUESTIONS = 10;

    public function handle(PracticeSessionCompleted $event): void
    {
        $session = $event->session;

        if ($session->questions_answered < self::DAILY_GOAL_QUESTIONS) {
            return;
        }

        $date = $session->started_at->toDateString();

        // Not firstOrCreate(): the 'date' cast serializes with a trailing
        // "00:00:00", so an exact-string WHERE on the plain date value
        // never matches the row it just inserted, and every subsequent
        // session on the same day would crash on the unique constraint.
        $existing = DailyGoalLog::where('child_id', $session->child_id)
            ->whereDate('date', $date)
            ->first();

        if ($existing) {
            return;
        }

        $log = DailyGoalLog::create([
            'child_id' => $session->child_id,
            'date' => $date,
            'goal_met' => true,
            'practice_session_id' => $session->id,
        ]);

        $child = $session->child;
        $parents = $child->family->users;

        if ($parents->isNotEmpty()) {
            Notification::send($parents, new DailyGoalAchieved($child, $session));
        }

        $log->forceFill(['notified_at' => now()])->save();
    }
}
