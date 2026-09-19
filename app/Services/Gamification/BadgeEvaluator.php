<?php

namespace App\Services\Gamification;

use App\Listeners\EvaluateDailyGoal;
use App\Models\Badge;
use App\Models\Child;
use App\Models\ChildFactStat;
use App\Models\PracticeSession;
use Illuminate\Support\Collection;

/**
 * Checks every badge a child has not earned yet against a just-completed
 * session and records the ones now earned. Earning is permanent and idempotent:
 * the (child, badge) pair is unique, so re-running never awards twice.
 */
class BadgeEvaluator
{
    /** @return Collection<int, Badge> The badges earned by this call. */
    public function evaluate(Child $child, PracticeSession $session): Collection
    {
        $earnedIds = $child->badges()->pluck('badges.id');

        $newlyEarned = Badge::query()
            ->whereNotIn('id', $earnedIds)
            ->get()
            ->filter(fn (Badge $badge) => $this->isEarned($badge, $child, $session))
            ->values();

        foreach ($newlyEarned as $badge) {
            $child->badges()->syncWithoutDetaching([$badge->id => ['earned_at' => now()]]);
        }

        return $newlyEarned;
    }

    private function isEarned(Badge $badge, Child $child, PracticeSession $session): bool
    {
        $criteria = $badge->criteria;

        return match ($criteria['type'] ?? null) {
            'first_session' => $session->questions_answered >= ($criteria['min_questions'] ?? 1),
            'streak_days' => $this->goalStreakDays($child, $session) >= $criteria['days'],
            'blitz' => $this->isBlitz($child, $session, $criteria),
            'row_mastery' => $this->masteredRow($child, $criteria),
            default => false,
        };
    }

    /**
     * Consecutive days, ending on the session's day, on which the daily goal
     * was met. Today is counted from the session itself: the goal listener
     * may not have written today's log yet when this runs.
     */
    private function goalStreakDays(Child $child, PracticeSession $session): int
    {
        $today = $session->started_at->copy()->startOfDay();

        $metDays = $child->dailyGoalLogs()
            ->where('goal_met', true)
            ->whereDate('date', '<=', $today->toDateString())
            ->pluck('date')
            ->mapWithKeys(fn ($date) => [$date->toDateString() => true])
            ->all();

        if ($session->questions_answered >= EvaluateDailyGoal::DAILY_GOAL_QUESTIONS) {
            $metDays[$today->toDateString()] = true;
        }

        $streak = 0;
        for ($day = $today->copy(); isset($metDays[$day->toDateString()]); $day->subDay()) {
            $streak++;
        }

        return $streak;
    }

    /**
     * A speed badge only makes sense where speed counts: for children whose
     * parents switched the speed bonus off it is never awarded, so nobody is
     * nudged towards rushing.
     */
    private function isBlitz(Child $child, PracticeSession $session, array $criteria): bool
    {
        $speedBonusEnabled = $child->exerciseSettings()
            ->where('exercise_type_id', $session->exercise_type_id)
            ->first()?->speed_bonus_enabled ?? true;

        if (! $speedBonusEnabled) {
            return false;
        }

        $correct = $session->attempts()->where('is_correct', true);

        if ($correct->count() < $criteria['min_correct_in_session']) {
            return false;
        }

        return $correct->avg('response_time_ms') <= $criteria['max_avg_response_ms'];
    }

    private function masteredRow(Child $child, array $criteria): bool
    {
        $stats = ChildFactStat::query()
            ->where('child_id', $child->id)
            ->whereHas('fact', fn ($fact) => $fact
                ->where('difficulty_group', $criteria['difficulty_group'])
                ->whereHas('exerciseType', fn ($type) => $type->where('key', $criteria['exercise_type'])))
            ->get(['attempts_total', 'attempts_correct']);

        $attempts = $stats->sum('attempts_total');

        if ($attempts < $criteria['min_attempts']) {
            return false;
        }

        if ($stats->where('attempts_total', '>', 0)->count() < $criteria['min_facts']) {
            return false;
        }

        return $stats->sum('attempts_correct') / $attempts >= $criteria['min_accuracy'];
    }
}
