<?php

namespace App\Listeners;

use App\Events\PracticeSessionCompleted;
use App\Models\DailyGoalLog;
use App\Notifications\DailyGoalAchieved;
use Illuminate\Support\Facades\Notification;

class EvaluateDailyGoal
{
    private const DAILY_GOAL_QUESTIONS = 10;

    public function handle(PracticeSessionCompleted $event): void
    {
        $session = $event->session;

        if ($session->questions_answered < self::DAILY_GOAL_QUESTIONS) {
            return;
        }

        $log = DailyGoalLog::firstOrCreate(
            ['child_id' => $session->child_id, 'date' => $session->started_at->toDateString()],
            ['goal_met' => true, 'practice_session_id' => $session->id],
        );

        if (! $log->wasRecentlyCreated) {
            return;
        }

        $child = $session->child;
        $parents = $child->family->users;

        if ($parents->isNotEmpty()) {
            Notification::send($parents, new DailyGoalAchieved($child, $session));
        }

        $log->forceFill(['notified_at' => now()])->save();
    }
}
