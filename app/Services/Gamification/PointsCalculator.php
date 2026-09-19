<?php

namespace App\Services\Gamification;

class PointsCalculator
{
    private const BASE_POINTS = 10;

    private const MAX_SPEED_BONUS = 10;

    /**
     * A correct answer is worth 10 points, plus up to 10 for beating the
     * target time (never negative: a slow but correct answer still earns the
     * base points), times a multiplier for long streaks within the session.
     * Parents can switch the speed bonus off per child.
     */
    public function forAnswer(
        bool $isCorrect,
        int $responseTimeMs,
        int $targetResponseMs,
        int $sessionStreakAfter,
        bool $speedBonusEnabled = true,
    ): int {
        if (! $isCorrect) {
            return 0;
        }

        $speedBonus = $speedBonusEnabled
            ? (int) round(min(max(($targetResponseMs - $responseTimeMs) / max($targetResponseMs, 1), 0), 1) * self::MAX_SPEED_BONUS)
            : 0;

        $rawPoints = self::BASE_POINTS + $speedBonus;

        $multiplier = match (true) {
            $sessionStreakAfter >= 10 => 1.5,
            $sessionStreakAfter >= 5 => 1.2,
            default => 1.0,
        };

        return (int) round($rawPoints * $multiplier);
    }
}
