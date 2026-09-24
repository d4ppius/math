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

    public function operator(): string
    {
        return '×';
    }

    public function emoji(): string
    {
        return '✖️';
    }

    public function gridDefinition(): array
    {
        return [
            'rows' => range(1, 9),
            'cols' => range(1, 10),
        ];
    }

    /** One group per row: 1er-Reihe to 9er-Reihe, shown as the plain number. */
    public function groups(): array
    {
        return array_combine(range(1, 9), array_map('strval', range(1, 9)));
    }

    public function groupsLabel(): string
    {
        return 'Reihen';
    }

    public function enabledByDefault(): bool
    {
        return true;
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

    /**
     * One rule per row (Reihe), the usual Einmaleins tricks: ×1 stays the
     * same, ×10 just appends a zero (checked first, it applies in every row),
     * ×2/×4 double (twice for ×4), ×5 is half of the ×10 fact, and ×3/×6/×9
     * build on a smaller or bigger known fact. ×7 and ×8 are derived from a
     * neighbouring row rather than counted directly.
     */
    public function hint(Fact $fact): array
    {
        $a = $fact->operand_a;
        $b = $fact->operand_b;

        if ($b === 10) {
            return ["{$a} × 10 = ".($a * 10)];
        }

        return match ($a) {
            1 => ["1 × {$b} = {$b}"],
            2 => ["{$b} + {$b} = ".($b * 2)],
            4 => ["{$b} + {$b} = ".($b * 2), ($b * 2).' + '.($b * 2).' = '.($b * 4)],
            5 => ["{$b} × 10 = ".($b * 10), ($b * 10).' ÷ 2 = '.($b * 5)],
            3 => ["2 × {$b} = ".($b * 2), ($b * 2)." + {$b} = ".($b * 3)],
            6 => ["5 × {$b} = ".($b * 5), ($b * 5)." + {$b} = ".($b * 6)],
            9 => ["10 × {$b} = ".($b * 10), ($b * 10)." − {$b} = ".($b * 9)],
            7 => ["6 × {$b} = ".($b * 6), ($b * 6)." + {$b} = ".($b * 7)],
            8 => ["4 × {$b} = ".($b * 4), ($b * 4).' + '.($b * 4).' = '.($b * 8)],
            default => ["{$a} × {$b} = ".($a * $b)],
        };
    }
}
