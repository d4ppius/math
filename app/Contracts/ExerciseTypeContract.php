<?php

namespace App\Contracts;

use App\Models\Fact;

/**
 * A pluggable kind of math exercise (multiplication, addition, …).
 * New exercise types are added by implementing this contract and
 * registering the class in config/exercise_types.php — no changes
 * to the session flow, adaptive selection, or dashboard are needed.
 */
interface ExerciseTypeContract
{
    public function key(): string;

    public function label(): string;

    /** A small emoji shown next to the name when the child chooses an exercise. */
    public function emoji(): string;

    /**
     * @return array{rows: int[], cols: int[]} the operand values shown as rows
     *                                         (operand_a) and columns (operand_b) of the parent heatmap.
     */
    public function gridDefinition(): array;

    /**
     * The difficulty groups parents can switch on, with the name shown to them.
     *
     * @return array<int, string> difficulty group => name
     */
    public function groups(): array;

    /** What the groups are called in the parent settings, e.g. "Reihen". */
    public function groupsLabel(): string;

    /** Whether the exercise starts switched on when it is first set up for a child. */
    public function enabledByDefault(): bool;

    /**
     * @return list<array{operand_a: int, operand_b: int, correct_answer: int, difficulty_group: int}>
     */
    public function generateFacts(): array;

    public function formatPrompt(Fact $fact): string;

    /**
     * Roughly how long a confident answer should take, used by the
     * adaptive selector's speed penalty and by the points calculator.
     */
    public function targetResponseMs(int $difficultyGroup): int;

    /**
     * @return int[] difficulty groups a brand-new child should start with.
     */
    public function defaultActiveGroups(): array;
}
