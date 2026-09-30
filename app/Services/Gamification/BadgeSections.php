<?php

namespace App\Services\Gamification;

use App\Models\Badge;
use App\Services\ExerciseTypes\ExerciseTypeRegistry;
use Illuminate\Support\Collection;

/**
 * Splits badges into the sections shown to people. The exercise-independent
 * badges are grouped by kind (Meilensteine, Level, Freunde vom Fuchs) so the
 * growing collection stays readable, then one section per exercise follows in
 * the order the exercises are registered.
 */
class BadgeSections
{
    /** Exercise-independent groups, in display order: label => the criteria types it holds. */
    private const GENERAL_GROUPS = [
        'Meilensteine' => ['first_session', 'streak_days', 'all_exercises_same_day'],
        'Level' => ['level_reached'],
        'Freunde vom Fuchs' => ['weekday'],
    ];

    public function __construct(private ExerciseTypeRegistry $registry) {}

    /**
     * @param  Collection<int, Badge>  $badges
     * @return list<array{label: string, exercise: bool, badges: Collection<int, Badge>}>
     */
    public function group(Collection $badges): array
    {
        $sections = [];

        foreach (self::GENERAL_GROUPS as $label => $types) {
            $inGroup = $badges->filter(fn (Badge $badge) => $badge->exerciseKey() === null
                && in_array($badge->criteria['type'] ?? null, $types, true))->values();

            if ($inGroup->isNotEmpty()) {
                $sections[] = ['label' => $label, 'exercise' => false, 'badges' => $inGroup];
            }
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
