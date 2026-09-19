<?php

namespace App\Services\Gamification;

/**
 * Translates a child's total points into one of ten levels. Purely derived
 * from the points, so there is nothing to store or migrate.
 *
 * A practice session is worth very roughly 300-650 points, so the steps grow:
 * the first level-up comes after about one session, the last one after a few
 * months of regular practice. Tune THRESHOLDS if that feels off.
 */
class LevelCalculator
{
    /** Minimum total points needed for each level (index 0 = level 1). */
    private const THRESHOLDS = [0, 400, 1200, 2500, 4500, 7500, 11500, 16500, 22500, 30000];

    /** @var list<array{string, string}> Title and emoji per level. */
    private const TITLES = [
        ['Rechen-Anfänger', '🌱'],
        ['Zahlen-Entdecker', '🔍'],
        ['Rechen-Lehrling', '📘'],
        ['Zahlen-Flitzer', '🏃'],
        ['Rechen-Profi', '⭐'],
        ['Knobel-Meister', '🧩'],
        ['Zahlen-Zauberer', '🪄'],
        ['Rechen-Ass', '🎯'],
        ['Mathe-Held', '🦸'],
        ['Rechenfuchs-Meister', '🦊'],
    ];

    /**
     * @return array{
     *     level: int,
     *     title: string,
     *     emoji: string,
     *     is_max: bool,
     *     progress: float,
     *     points_to_next: int|null,
     *     next_level: int|null,
     * }
     */
    public function forPoints(int $points): array
    {
        $points = max(0, $points);

        $index = 0;
        foreach (self::THRESHOLDS as $i => $threshold) {
            if ($points >= $threshold) {
                $index = $i;
            }
        }

        $isMax = $index === count(self::THRESHOLDS) - 1;

        $progress = 1.0;
        $pointsToNext = null;

        if (! $isMax) {
            $from = self::THRESHOLDS[$index];
            $to = self::THRESHOLDS[$index + 1];
            $progress = ($points - $from) / ($to - $from);
            $pointsToNext = $to - $points;
        }

        [$title, $emoji] = self::TITLES[$index];

        return [
            'level' => $index + 1,
            'title' => $title,
            'emoji' => $emoji,
            'is_max' => $isMax,
            'progress' => round($progress, 4),
            'points_to_next' => $pointsToNext,
            'next_level' => $isMax ? null : $index + 2,
        ];
    }
}
