<?php

namespace App\Services\ExerciseTypes;

use App\Contracts\ExerciseTypeContract;
use App\Models\Fact;

/**
 * The small multiplication table, 1×1 through 9×10.
 */
class MultiplicationExerciseType implements ExerciseTypeContract
{
    public function key(): string
    {
        return 'multiplication';
    }

    public function label(): string
    {
        return 'Einmaleins';
    }

    public function gridDefinition(): array
    {
        return [
            'rows' => range(1, 9),
            'cols' => range(1, 10),
        ];
    }

    public function generateFacts(): array
    {
        $facts = [];

        foreach (range(1, 9) as $a) {
            foreach (range(1, 10) as $b) {
                $facts[] = [
                    'operand_a' => $a,
                    'operand_b' => $b,
                    'correct_answer' => $a * $b,
                    'difficulty_group' => $a,
                ];
            }
        }

        return $facts;
    }

    public function formatPrompt(Fact $fact): string
    {
        return "{$fact->operand_a} × {$fact->operand_b}";
    }

    public function targetResponseMs(int $difficultyGroup): int
    {
        // Small rows (1er, 2er, 10er) are usually answered faster than
        // the trickier middle rows (6er–9er).
        return match (true) {
            $difficultyGroup <= 2 => 2500,
            $difficultyGroup >= 6 => 4500,
            default => 3500,
        };
    }

    public function defaultActiveGroups(): array
    {
        return [1, 2];
    }
}
