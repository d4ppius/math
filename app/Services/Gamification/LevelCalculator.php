<?php

namespace App\Services\Gamification;

/**
 * Translates points into one of ten levels — since 2026-09, one level per
 * exercise (see ChildExerciseSetting::level()), not one shared level for a
 * child overall. Purely derived from the points, so there is nothing to
 * store or migrate.
 *
 * Calibrated from three simulated practice paces on a single exercise
 * (see docs/plan-level-pro-uebung.md): a typical pace reaches the top level
 * after roughly 10 weeks of regular practice (~54,500 points), a fast pace
 * on easy content after about a month (~144,650 points would be reached,
 * but the curve below tops out at 55,000 so it arrives sooner), a slow pace
 * after several months. Tune THRESHOLDS if real usage suggests otherwise.
 */
class LevelCalculator
{
    /** Minimum points needed for each level (index 0 = level 1). */
    private const THRESHOLDS = [0, 500, 1500, 3500, 7000, 13000, 21000, 31000, 42000, 55000];

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
