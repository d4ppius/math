<?php

namespace Tests\Unit;

use App\Models\Fact;
use App\Services\ExerciseTypes\DoublingExerciseType;
use PHPUnit\Framework\TestCase;

class DoublingExerciseTypeTest extends TestCase
{
    private DoublingExerciseType $type;

    protected function setUp(): void
    {
        $this->type = new DoublingExerciseType;
    }

    public function test_it_identifies_itself(): void
    {
        $this->assertSame('doubling', $this->type->key());
        $this->assertSame('Verdoppeln & Halbieren', $this->type->label());
        $this->assertSame('Aufgabenarten', $this->type->groupsLabel());
    }

    public function test_it_generates_doubling_and_halving_tasks_without_key_collisions(): void
    {
        $facts = $this->type->generateFacts();

        $this->assertCount(50, $facts);

        $pairs = array_map(fn ($fact) => $fact['operand_a'].'-'.$fact['operand_b'], $facts);
        $this->assertCount(50, array_unique($pairs), 'doubling and halving of the same number never collide');

        foreach ($facts as $fact) {
            if ($fact['operand_b'] === 2) {
                $this->assertSame($fact['operand_a'] * 2, $fact['correct_answer']);
                $this->assertLessThanOrEqual(50, $fact['correct_answer']);
            } else {
                $this->assertSame(0, $fact['operand_b']);
                $this->assertSame(0, $fact['operand_a'] % 2, 'halving only for even numbers');
                $this->assertSame(intdiv($fact['operand_a'], 2), $fact['correct_answer']);
            }
        }
    }

    public function test_the_groups_double_as_number_ranges_with_the_expected_sizes(): void
    {
        $sizes = array_fill_keys(array_keys($this->type->groups()), 0);

        foreach ($this->type->generateFacts() as $fact) {
            $this->assertArrayHasKey($fact['difficulty_group'], $sizes);
            $sizes[$fact['difficulty_group']]++;
        }

        $this->assertSame([1 => 10, 2 => 15, 3 => 10, 4 => 15], $sizes);
    }

    public function test_the_prompt_names_the_operation(): void
    {
        $this->assertSame('Doppelte von 7', $this->type->formatPrompt(new Fact(['operand_a' => 7, 'operand_b' => 2])));
        $this->assertSame('Hälfte von 16', $this->type->formatPrompt(new Fact(['operand_a' => 16, 'operand_b' => 0])));
    }

    public function test_the_hints_use_repeated_addition_and_division(): void
    {
        $this->assertSame(['7 + 7 = 14'], $this->type->hint(new Fact(['operand_a' => 7, 'operand_b' => 2, 'correct_answer' => 14])));
        $this->assertSame(['16 ÷ 2 = 8'], $this->type->hint(new Fact(['operand_a' => 16, 'operand_b' => 0, 'correct_answer' => 8])));
    }

    public function test_the_heatmap_columns_are_labelled_by_operation(): void
    {
        $this->assertSame('Doppelte', $this->type->heatmapColumnLabel(2));
        $this->assertSame('Hälfte', $this->type->heatmapColumnLabel(0));
        $this->assertSame('7', $this->type->heatmapRowLabel(7));
    }

    public function test_it_starts_with_the_easiest_group_and_is_off_until_parents_enable_it(): void
    {
        $this->assertSame([1], $this->type->defaultActiveGroups());
        $this->assertFalse($this->type->enabledByDefault());
    }
}
