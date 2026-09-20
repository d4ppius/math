<?php

namespace App\Services\ExerciseTypes;

use App\Contracts\ExerciseTypeContract;
use App\Models\Fact;

/**
 * Addition within 20 ("Einspluseins"): both summands from 1 to 10, so every
 * sum stays at or below 20 (100 tasks). The difficulty group is the kind of
 * task rather than a row: plain sums up to 10, sums with the ten, and the
 * step over the ten.
 */
class AdditionExerciseType implements ExerciseTypeContract
{
    public const GROUP_UP_TO_TEN = 1;

    public const GROUP_WITH_TEN = 2;

    public const GROUP_OVER_TEN = 3;

    public function key(): string
    {
        return 'addition';
    }

    public function label(): string
    {
        return 'Plus bis 20';
    }

    public function operator(): string
    {
        return '+';
    }

    public function emoji(): string
    {
        return '➕';
    }

    /** Summand a in the rows, summand b in the columns. */
    public function gridDefinition(): array
    {
        return [
            'rows' => range(1, 10),
            'cols' => range(1, 10),
        ];
    }

    /** @return array<int, string> difficulty group => name shown to parents */
    public function groups(): array
    {
        return [
            self::GROUP_UP_TO_TEN => 'Plus bis 10',
            self::GROUP_WITH_TEN => 'Plus mit der 10',
            self::GROUP_OVER_TEN => 'Zehnerübergang',
        ];
    }

    /** What the groups are called in the parent settings ("Aktive …"). */
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

        foreach (range(1, 10) as $a) {
            foreach (range(1, 10) as $b) {
                $facts[] = [
                    'operand_a' => $a,
                    'operand_b' => $b,
                    'correct_answer' => $a + $b,
                    'difficulty_group' => $this->groupFor($a, $b),
                ];
            }
        }

        return $facts;
    }

    public function formatPrompt(Fact $fact): string
    {
        return "{$fact->operand_a} + {$fact->operand_b}";
    }

    public function targetResponseMs(int $difficultyGroup): int
    {
        // Sums up to 10 and "plus 10" are recalled quickly; the step over the
        // ten takes a moment longer.
        return $difficultyGroup === self::GROUP_OVER_TEN ? 3500 : 2000;
    }

    public function defaultActiveGroups(): array
    {
        return [self::GROUP_UP_TO_TEN];
    }

    private function groupFor(int $a, int $b): int
    {
        return match (true) {
            $a + $b <= 10 => self::GROUP_UP_TO_TEN,
            $a === 10 || $b === 10 => self::GROUP_WITH_TEN,
            default => self::GROUP_OVER_TEN,
        };
    }
}
