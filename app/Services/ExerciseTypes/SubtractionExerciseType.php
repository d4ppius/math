<?php

namespace App\Services\ExerciseTypes;

use App\Contracts\ExerciseTypeContract;
use App\Models\Fact;

/**
 * Subtraction within 20 ("Einsminuseins"), the cycle-1 counterpart to
 * "Plus bis 20". Built as the mirror of the addition facts: for every result
 * 0..10 and every subtrahend 1..10 the minuend is result + subtrahend (2..20),
 * so subtrahend and result each stay at or below 10 (110 tasks, no negative
 * results). Difficulty groups mirror addition: within the first ten, with the
 * ten as a landmark, and the step over the ten (borrowing).
 */
class SubtractionExerciseType implements ExerciseTypeContract
{
    public const GROUP_UP_TO_TEN = 1;

    public const GROUP_WITH_TEN = 2;

    public const GROUP_OVER_TEN = 3;

    public function key(): string
    {
        return 'subtraction';
    }

    public function label(): string
    {
        return 'Minus bis 20';
    }

    public function operator(): string
    {
        return '−';
    }

    public function emoji(): string
    {
        return '➖';
    }

    /** Minuend a in the rows, subtrahend b in the columns. */
    public function gridDefinition(): array
    {
        return [
            'rows' => range(1, 20),
            'cols' => range(1, 10),
        ];
    }

    /** @return array<int, string> difficulty group => name shown to parents */
    public function groups(): array
    {
        return [
            self::GROUP_UP_TO_TEN => 'Minus bis 10',
            self::GROUP_WITH_TEN => 'Minus mit der 10',
            self::GROUP_OVER_TEN => 'Zehnerübergang',
        ];
    }

    public function groupsLabel(): string
    {
        return 'Aufgabenarten';
    }

    /** Parents switch this exercise on per child; it is off until then. */
    public function enabledByDefault(): bool
    {
        return false;
    }

    public function generateFacts(): array
    {
        $facts = [];

        foreach (range(0, 10) as $result) {
            foreach (range(1, 10) as $b) {
                $a = $result + $b;

                $facts[] = [
                    'operand_a' => $a,
                    'operand_b' => $b,
                    'correct_answer' => $result,
                    'difficulty_group' => $this->groupFor($a, $b),
                ];
            }
        }

        return $facts;
    }

    public function formatPrompt(Fact $fact): string
    {
        return "{$fact->operand_a} − {$fact->operand_b}";
    }

    public function targetResponseMs(int $difficultyGroup): int
    {
        // The step over the ten takes a moment longer than staying within it.
        return $difficultyGroup === self::GROUP_OVER_TEN ? 3500 : 2000;
    }

    public function defaultActiveGroups(): array
    {
        return [self::GROUP_UP_TO_TEN];
    }

    private function groupFor(int $a, int $b): int
    {
        if ($a < 10) {
            return self::GROUP_UP_TO_TEN;
        }

        // The 10 as a clean landmark (minuend of 10, or taking away a whole
        // ten), or no borrow needed because the ones digit already covers it.
        if ($a === 10 || $b === 10 || $b <= $a % 10) {
            return self::GROUP_WITH_TEN;
        }

        return self::GROUP_OVER_TEN;
    }

    /**
     * The two halbschriftliche strategies the Lehrplan names, each chain
     * ending exactly at the result so every fact can be checked mechanically:
     * a plain subtraction when no borrow is needed, and "step down to ten
     * first, then take the rest" for the Zehnerübergang.
     */
    public function hint(Fact $fact): array
    {
        $a = $fact->operand_a;
        $b = $fact->operand_b;
        $result = $a - $b;
        $ones = $a % 10;

        // Borrow needed (Zehnerübergang), only for a teen minuend: drop to the
        // ten by taking away the ones, then subtract what remains.
        if ($a > 10 && $a < 20 && $b > $ones) {
            $rest = $b - $ones;

            return ["{$a} − {$ones} = 10", "10 − {$rest} = {$result}"];
        }

        return ["{$a} − {$b} = {$result}"];
    }
}
