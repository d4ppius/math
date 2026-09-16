<?php

namespace Database\Seeders;

use App\Models\ExerciseType;
use App\Services\ExerciseTypes\ExerciseTypeRegistry;
use Illuminate\Database\Seeder;

class ExerciseTypeSeeder extends Seeder
{
    public function run(ExerciseTypeRegistry $registry): void
    {
        foreach ($registry->all() as $implementation) {
            $exerciseType = ExerciseType::updateOrCreate(
                ['key' => $implementation->key()],
                ['name' => $implementation->label(), 'is_active' => true],
            );

            foreach ($implementation->generateFacts() as $fact) {
                $exerciseType->facts()->updateOrCreate(
                    ['operand_a' => $fact['operand_a'], 'operand_b' => $fact['operand_b']],
                    ['correct_answer' => $fact['correct_answer'], 'difficulty_group' => $fact['difficulty_group']],
                );
            }
        }
    }
}
