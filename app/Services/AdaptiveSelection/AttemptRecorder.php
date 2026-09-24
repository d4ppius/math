<?php

namespace App\Services\AdaptiveSelection;

use App\Models\ChildFactStat;
use App\Models\PracticeSession;
use App\Models\SessionAttempt;
use App\Services\ExerciseTypes\ExerciseTypeRegistry;
use App\Services\Gamification\PointsCalculator;
use Illuminate\Support\Carbon;

/**
 * Records one answered question: grades it against the server-held
 * open question, updates the child's per-fact stats and priority score,
 * awards points, and advances the session counters.
 */
class AttemptRecorder
{
    public function __construct(
        private FactPriorityCalculator $priorityCalculator,
        private PointsCalculator $pointsCalculator,
        private ExerciseTypeRegistry $registry,
    ) {}

    public function record(PracticeSession $session, int $givenAnswer): array
    {
        $fact = $session->currentFact;
        $issuedAt = $session->current_question_issued_at;

        $now = Carbon::now();
        $responseTimeMs = max(0, (int) round($issuedAt->diffInMilliseconds($now)));
        $isCorrect = $givenAnswer === $fact->correct_answer;

        $implementation = $this->registry->get($session->exerciseType->key);
        $targetMs = $implementation->targetResponseMs($fact->difficulty_group);

        $stat = ChildFactStat::firstOrNew([
            'child_id' => $session->child_id,
            'fact_id' => $fact->id,
        ]);

        $stat->attempts_total = ($stat->attempts_total ?? 0) + 1;
        $stat->attempts_correct = ($stat->attempts_correct ?? 0) + ($isCorrect ? 1 : 0);
        $stat->avg_response_ms = $stat->avg_response_ms
            ? (int) round($stat->avg_response_ms * 0.7 + $responseTimeMs * 0.3)
            : $responseTimeMs;
        $stat->last_response_ms = $responseTimeMs;
        $stat->current_streak = $isCorrect ? ($stat->current_streak ?? 0) + 1 : 0;
        $stat->last_practiced_at = $now;
        $stat->last_result = $isCorrect;
        $stat->priority_score = $this->priorityCalculator->calculate($stat, $targetMs);
        $stat->save();

        $sessionStreakAfter = $this->currentSessionStreak($session, $isCorrect);
        $setting = $session->child->exerciseSettings()
            ->where('exercise_type_id', $session->exercise_type_id)
            ->first();

        $points = $this->pointsCalculator->forAnswer($isCorrect, $responseTimeMs, $targetMs, $sessionStreakAfter, $setting?->speed_bonus_enabled ?? true);

        SessionAttempt::create([
            'practice_session_id' => $session->id,
            'fact_id' => $fact->id,
            'given_answer' => $givenAnswer,
            'is_correct' => $isCorrect,
            'response_time_ms' => $responseTimeMs,
            'points_awarded' => $points,
            'question_issued_at' => $issuedAt,
            'answered_at' => $now,
        ]);

        $session->questions_answered++;
        $session->questions_correct += $isCorrect ? 1 : 0;
        $session->total_points += $points;
        $session->current_fact_id = null;
        $session->current_question_issued_at = null;
        $session->save();

        $session->child()->increment('total_points', $points);
        // total_points is now a display-only lifetime sum; the per-exercise
        // points below are what levels and progress bars are based on.
        $setting?->increment('points', $points);

        return [
            'is_correct' => $isCorrect,
            'correct_answer' => $fact->correct_answer,
            'points_awarded' => $points,
            'session_total_points' => $session->total_points,
        ];
    }

    private function currentSessionStreak(PracticeSession $session, bool $latestIsCorrect): int
    {
        if (! $latestIsCorrect) {
            return 0;
        }

        $priorCorrectStreak = 0;

        foreach ($session->attempts()->latest('id')->pluck('is_correct') as $wasCorrect) {
            if (! $wasCorrect) {
                break;
            }
            $priorCorrectStreak++;
        }

        return $priorCorrectStreak + 1;
    }
}
