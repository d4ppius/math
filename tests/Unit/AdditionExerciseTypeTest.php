<?php

namespace Tests\Unit;

use App\Models\Fact;
use App\Services\ExerciseTypes\AdditionExerciseType;
use PHPUnit\Framework\TestCase;

class AdditionExerciseTypeTest extends TestCase
{
    private AdditionExerciseType $type;

    protected function setUp(): void
    {
        $this->type = new AdditionExerciseType;
    }

    public function test_it_identifies_itself(): void
    {
        $this->assertSame('addition', $this->type->key());
        $this->assertSame('Plus bis 20', $this->type->label());
        $this->assertSame('Aufgabenarten', $this->type->groupsLabel());
    }

    public function test_it_generates_exactly_one_hundred_correct_tasks_within_twenty(): void
    {
        $facts = $this->type->generateFacts();

        $this->assertCount(100, $facts);

        foreach ($facts as $fact) {
            $this->assertGreaterThanOrEqual(1, $fact['operand_a']);
            $this->assertLessThanOrEqual(10, $fact['operand_a']);
            $this->assertGreaterThanOrEqual(1, $fact['operand_b']);
            $this->assertLessThanOrEqual(10, $fact['operand_b']);
            $this->assertSame($fact['operand_a'] + $fact['operand_b'], $fact['correct_answer']);
            $this->assertLessThanOrEqual(20, $fact['correct_answer']);
        }
    }

    public function test_every_pair_of_summands_appears_exactly_once(): void
    {
        $pairs = array_map(fn ($fact) => $fact['operand_a'].'+'.$fact['operand_b'], $this->type->generateFacts());

        $this->assertCount(100, array_unique($pairs));
    }

    public function test_every_task_belongs_to_one_known_group_and_the_groups_have_the_planned_sizes(): void
    {
        $sizes = array_fill_keys(array_keys($this->type->groups()), 0);

        foreach ($this->type->generateFacts() as $fact) {
            $this->assertArrayHasKey($fact['difficulty_group'], $sizes);
            $sizes[$fact['difficulty_group']]++;
        }

        $this->assertSame([1 => 45, 2 => 19, 3 => 36], $sizes);
    }

    public function test_the_group_rules(): void
    {
        $group = fn (int $a, int $b) => collect($this->type->generateFacts())
            ->first(fn ($fact) => $fact['operand_a'] === $a && $fact['operand_b'] === $b)['difficulty_group'];

        $this->assertSame(1, $group(3, 4));
        $this->assertSame(1, $group(5, 5));   // exactly 10
        $this->assertSame(1, $group(1, 9));
        $this->assertSame(2, $group(10, 1));  // with the ten, sum above 10
        $this->assertSame(2, $group(7, 10));
        $this->assertSame(2, $group(10, 10));
        $this->assertSame(3, $group(8, 5));   // the step over the ten
        $this->assertSame(3, $group(9, 9));
        $this->assertSame(3, $group(2, 9));
    }

    public function test_the_prompt_is_written_as_a_sum(): void
    {
        $fact = new Fact(['operand_a' => 7, 'operand_b' => 8]);

        $this->assertSame('7 + 8', $this->type->formatPrompt($fact));
    }

    public function test_the_step_over_the_ten_gets_a_longer_target_time(): void
    {
        $this->assertSame(2000, $this->type->targetResponseMs(1));
        $this->assertSame(2000, $this->type->targetResponseMs(2));
        $this->assertSame(3500, $this->type->targetResponseMs(3));
    }

    public function test_it_starts_with_the_easiest_group_and_is_off_until_parents_enable_it(): void
    {
        $this->assertSame([1], $this->type->defaultActiveGroups());
        $this->assertFalse($this->type->enabledByDefault());
    }

    public function test_the_grid_covers_all_summands(): void
    {
        $grid = $this->type->gridDefinition();

        $this->assertSame(range(1, 10), $grid['rows']);
        $this->assertSame(range(1, 10), $grid['cols']);
    }
}
