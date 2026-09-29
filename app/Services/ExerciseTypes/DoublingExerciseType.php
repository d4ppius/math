<?php

namespace App\Services\ExerciseTypes;

use App\Contracts\ExerciseTypeContract;
use App\Models\Fact;

/**
 * Doubling and halving ("Verdoppeln & Halbieren"), a cycle-1 skill and the
 * bridge from addition to multiplication (doubling = ×2; ×5 is half of ×10).
 *
 * These tasks are single-operand, so the operation is encoded in operand_b to
 * fit the two-operand Fact model without key collisions: operand_b = 2 means
 * "double a", operand_b = 0 means "halve a". Halving only exists for even
 * numbers. The four difficulty groups double as parent-selectable number
 * ranges (Verdoppeln bis 10/50, Halbieren bis 20/50).
 */
class DoublingExerciseType implements ExerciseTypeContract
{
    public const GROUP_DOUBLE_TO_10 = 1;

    public const GROUP_DOUBLE_TO_50 = 2;

    public const GROUP_HALVE_TO_20 = 3;

    public const GROUP_HALVE_TO_50 = 4;

    private const DOUBLE = 2;

    private const HALVE = 0;

    public function key(): string
    {
        return 'doubling';
    }

    public function label(): string
    {
        return 'Verdoppeln & Halbieren';
    }

    public function operator(): string
    {
        return '';
    }

    public function emoji(): string
    {
        return '🔁';
    }

    /** Rows are the numbers, the two columns are doubling and halving. */
    public function gridDefinition(): array
    {
        return [
            'rows' => range(1, 50),
            'cols' => [self::DOUBLE, self::HALVE],
        ];
    }

    public function heatmapColumnLabel(int $col): string
    {
        return $col === self::DOUBLE ? 'Doppelte' : 'Hälfte';
    }

    public function heatmapRowLabel(int $row): string
    {
        return (string) $row;
    }

    /** @return array<int, string> difficulty group => name shown to parents */
    public function groups(): array
    {
        return [
            self::GROUP_DOUBLE_TO_10 => 'Verdoppeln bis 10',
            self::GROUP_DOUBLE_TO_50 => 'Verdoppeln bis 50',
            self::GROUP_HALVE_TO_20 => 'Halbieren bis 20',
            self::GROUP_HALVE_TO_50 => 'Halbieren bis 50',
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

        // Verdoppeln: a = 1..25 -> results 2..50, split into "bis 10" / "bis 50".
        foreach (range(1, 25) as $a) {
            $facts[] = [
                'operand_a' => $a,
                'operand_b' => self::DOUBLE,
                'correct_answer' => $a * 2,
                'difficulty_group' => $a <= 10 ? self::GROUP_DOUBLE_TO_10 : self::GROUP_DOUBLE_TO_50,
            ];
        }

        // Halbieren: even a = 2..50 -> results 1..25, split into "bis 20" / "bis 50".
        foreach (range(1, 25) as $half) {
            $a = $half * 2;
            $facts[] = [
                'operand_a' => $a,
                'operand_b' => self::HALVE,
                'correct_answer' => $half,
                'difficulty_group' => $a <= 20 ? self::GROUP_HALVE_TO_20 : self::GROUP_HALVE_TO_50,
            ];
        }

        return $facts;
    }

    public function formatPrompt(Fact $fact): string
    {
        return $fact->operand_b === self::DOUBLE
            ? "Doppelte von {$fact->operand_a}"
            : "Hälfte von {$fact->operand_a}";
    }

    public function targetResponseMs(int $difficultyGroup): int
    {
        // Doubling and halving are quick recall once understood.
        return 2500;
    }

    public function defaultActiveGroups(): array
    {
        return [self::GROUP_DOUBLE_TO_10];
    }

    /**
     * Doubling as repeated addition (the cycle-1 idea), halving as division by
     * two (which always divides evenly here, since only even numbers occur).
     * Each chain ends at the result so every fact is mechanically checkable.
     */
    public function hint(Fact $fact): array
    {
        $a = $fact->operand_a;

        if ($fact->operand_b === self::DOUBLE) {
            return ["{$a} + {$a} = ".($a * 2)];
        }

        return ["{$a} ÷ 2 = ".intdiv($a, 2)];
    }
}
