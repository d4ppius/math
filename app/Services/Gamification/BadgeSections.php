<?php

namespace App\Services\Gamification;

use App\Models\Badge;
use App\Services\ExerciseTypes\ExerciseTypeRegistry;
use Illuminate\Support\Collection;

/**
 * Splits badges into the sections shown to people: "Allgemein" for badges that
 * belong to no single exercise, then one section per exercise (in the order the
 * exercises are registered), so the collection stays readable with several
 * exercises.
 */
class BadgeSections
{
    public function __construct(private ExerciseTypeRegistry $registry) {}

    /**
     * @param  Collection<int, Badge>  $badges
     * @return list<array{label: string, exercise: bool, badges: Collection<int, Badge>}>
     */
    public function group(Collection $badges): array
    {
        $general = $badges->filter(fn (Badge $badge) => $badge->exerciseKey() === null)->values();

        $sections = [];

        if ($general->isNotEmpty()) {
            $sections[] = ['label' => 'Allgemein', 'exercise' => false, 'badges' => $general];
        }

        foreach (array_keys(config('exercise_types', [])) as $key) {
            $forExercise = $badges->filter(fn (Badge $badge) => $badge->exerciseKey() === $key)->values();

            if ($forExercise->isNotEmpty()) {
                $sections[] = ['label' => $this->registry->get($key)->label(), 'exercise' => true, 'badges' => $forExercise];
            }
        }

        return $sections;
    }
}
