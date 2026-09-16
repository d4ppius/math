<?php

namespace App\Services\AdaptiveSelection;

use App\Models\Child;
use App\Models\ChildExerciseSetting;
use App\Models\Fact;
use App\Models\PracticeSession;
use App\Services\ExerciseTypes\ExerciseTypeRegistry;

/**
 * Picks the next fact to ask within a practice session: weighted-random
 * over the child's active difficulty groups, favouring facts that are
 * wrong, slow, or not practiced in a while — without ever being fully
 * predictable or excluding the facts the child already knows well.
 */
class WeightedFactSelector
{
    private const AVOID_LAST_N = 3;

    public function __construct(
        private FactPriorityCalculator $priorityCalculator,
        private ExerciseTypeRegistry $registry,
    ) {}

    public function next(Child $child, ChildExerciseSetting $settings, PracticeSession $session): ?Fact
    {
        $implementation = $this->registry->get($settings->exerciseType->key);

        $recentFactIds = $session->attempts()
            ->latest('id')
            ->limit(self::AVOID_LAST_N)
            ->pluck('fact_id');

        $candidates = Fact::where('exercise_type_id', $settings->exercise_type_id)
            ->whereIn('difficulty_group', $settings->active_groups)
            ->when($recentFactIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $recentFactIds))
            ->with(['childFactStats' => fn ($query) => $query->where('child_id', $child->id)])
            ->get();

        if ($candidates->isEmpty()) {
            // Every active fact was just asked (tiny active_groups) — allow repeats.
            $candidates = Fact::where('exercise_type_id', $settings->exercise_type_id)
                ->whereIn('difficulty_group', $settings->active_groups)
                ->with(['childFactStats' => fn ($query) => $query->where('child_id', $child->id)])
                ->get();
        }

        if ($candidates->isEmpty()) {
            return null;
        }

        $weights = $candidates->map(function (Fact $fact) use ($implementation) {
            $stat = $fact->childFactStats->first();

            return $this->priorityCalculator->calculate($stat, $implementation->targetResponseMs($fact->difficulty_group));
        });

        $totalWeight = $weights->sum();
        $roll = mt_rand() / mt_getrandmax() * $totalWeight;

        $cumulative = 0.0;
        foreach ($candidates as $index => $fact) {
            $cumulative += $weights[$index];
            if ($roll <= $cumulative) {
                return $fact;
            }
        }

        return $candidates->last();
    }
}
