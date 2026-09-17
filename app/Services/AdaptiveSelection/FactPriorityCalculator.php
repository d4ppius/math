<?php

namespace App\Services\AdaptiveSelection;

use App\Models\ChildFactStat;
use Illuminate\Support\Carbon;

/**
 * Turns a child's history with one fact into a priority weight: how much
 * more often it should be shown than a fact the child already masters.
 * Unknown facts (no stats yet) get the maximum weight so new rows are
 * introduced quickly.
 */
class FactPriorityCalculator
{
    private const WEIGHT_ERROR = 0.5;

    private const WEIGHT_SPEED = 0.3;

    private const WEIGHT_RECENCY = 0.2;

    private const EPSILON = 0.05;

    public function calculate(?ChildFactStat $stat, int $targetResponseMs): float
    {
        if (! $stat || $stat->attempts_total === 0) {
            return 1.0 + self::EPSILON;
        }

        $errorRate = 1 - ($stat->attempts_correct / $stat->attempts_total);

        $speedPenalty = min(max($stat->avg_response_ms / max($targetResponseMs, 1), 0.3), 2.0) / 2.0;

        $daysSinceLast = $stat->last_practiced_at
            ? max(0, $stat->last_practiced_at->diffInDays(Carbon::now(), false))
            : 999;
        $recencyFactor = min($daysSinceLast / 7, 1.0);

        return self::WEIGHT_ERROR * $errorRate
            + self::WEIGHT_SPEED * $speedPenalty
            + self::WEIGHT_RECENCY * $recencyFactor
            + self::EPSILON;
    }
}
