<?php

namespace App\Services\ExerciseTypes;

use App\Models\Child;
use App\Models\ExerciseType;

/**
 * Ensures a child has a ChildExerciseSetting row for every registered,
 * seeded exercise type. Idempotent, so it can be called both right after
 * creating a child and defensively whenever the settings page is opened
 * (e.g. to self-heal a child created before an exercise type existed).
 */
class ExerciseSettingsProvisioner
{
    public function __construct(private ExerciseTypeRegistry $registry) {}

    public function ensureDefaultsFor(Child $child): void
    {
        foreach ($this->registry->all() as $implementation) {
            $exerciseType = ExerciseType::where('key', $implementation->key())->first();

            if (! $exerciseType) {
                // Not seeded yet (e.g. exercise_types/facts migration ran
                // but ExerciseTypeSeeder hasn't) — nothing to attach to.
                continue;
            }

            $child->exerciseSettings()->firstOrCreate(
                ['exercise_type_id' => $exerciseType->id],
                [
                    'active_groups' => $implementation->defaultActiveGroups(),
                    'session_duration_minutes' => 10,
                    'target_frequency' => 'daily',
                    'sound_enabled' => true,
                ],
            );
        }
    }
}
