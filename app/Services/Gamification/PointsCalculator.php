<?php

namespace App\Services\Gamification;

class PointsCalculator
{
    private const BASE_POINTS = 10;

    private const MAX_SPEED_BONUS = 10;

    public function forAnswer(bool $isCorrect, int $responseTimeMs, int $targetResponseMs, int $sessionStreakAfter): int
    {
        if (! $isCorrect) {
            return 0;
        }

        $speedBonus = (int) round(
            min(max(($targetResponseMs - $responseTimeMs) / max($targetResponseMs, 1), -1), 1) * self::MAX_SPEED_BONUS
        );

        $rawPoints = self::BASE_POINTS + $speedBonus;

        $multiplier = match (true) {
            $sessionStreakAfter >= 10 => 1.5,
            $sessionStreakAfter >= 5 => 1.2,
            default => 1.0,
        };

        return (int) round($rawPoints * $multiplier);
    }
}
