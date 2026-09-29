<?php

namespace Tests\Unit;

use App\Models\Fact;
use App\Services\ExerciseTypes\SubtractionExerciseType;
use PHPUnit\Framework\TestCase;

class SubtractionExerciseTypeTest extends TestCase
{
    private SubtractionExerciseType $type;

    protected function setUp(): void
    {
        $this->type = new SubtractionExerciseType;
    }

    public function test_it_identifies_itself(): void
    {
        $this->assertSame('subtraction', $this->type->key());
        $this->assertSame('Minus bis 20', $this->type->label());
        $this->assertSame('−', $this->type->operator());
        $this->assertSame('Aufgabenarten', $this->type->groupsLabel());
    }

    public function test_it_generates_correct_non_negative_tasks_within_twenty(): void
    {
        $facts = $this->type->generateFacts();

        $this->assertCount(110, $facts);

        foreach ($facts as $fact) {
            $this->assertGreaterThanOrEqual(1, $fact['operand_a']);
            $this->assertLessThanOrEqual(20, $fact['operand_a']);
            $this->assertGreaterThanOrEqual(1, $fact['operand_b']);
            $this->assertLessThanOrEqual(10, $fact['operand_b']);
            $this->assertSame($fact['operand_a'] - $fact['operand_b'], $fact['correct_answer']);
            $this->assertGreaterThanOrEqual(0, $fact['correct_answer'], 'no negative results');
            $this->assertLessThanOrEqual(10, $fact['correct_answer']);
        }
    }

    public function test_every_pair_appears_exactly_once(): void
    {
        $pairs = array_map(fn ($fact) => $fact['operand_a'].'-'.$fact['operand_b'], $this->type->generateFacts());

        $this->assertCount(110, array_unique($pairs));
    }

    public function test_every_task_belongs_to_one_known_group_with_the_expected_sizes(): void
    {
        $sizes = array_fill_keys(array_keys($this->type->groups()), 0);

        foreach ($this->type->generateFacts() as $fact) {
            $this->assertArrayHasKey($fact['difficulty_group'], $sizes);
            $sizes[$fact['difficulty_group']]++;
        }

        $this->assertSame([1 => 45, 2 => 29, 3 => 36], $sizes);
    }

    public function test_the_group_rules(): void
    {
        $group = fn (int $a, int $b) => collect($this->type->generateFacts())
            ->first(fn ($fact) => $fact['operand_a'] === $a && $fact['operand_b'] === $b)['difficulty_group'];

        $this->assertSame(1, $group(9, 4));   // within the first ten
        $this->assertSame(2, $group(10, 6));  // the ten as minuend
        $this->assertSame(2, $group(13, 3));  // lands exactly on ten, no borrow
        $this->assertSame(2, $group(20, 10)); // takes away a whole ten
        $this->assertSame(3, $group(13, 8));  // the step over the ten
        $this->assertSame(3, $group(11, 5));  // borrow needed
    }

    public function test_the_prompt_is_written_as_a_difference(): void
    {
        $fact = new Fact(['operand_a' => 13, 'operand_b' => 8]);

        $this->assertSame('13 − 8', $this->type->formatPrompt($fact));
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

    public function test_the_borrow_hint_steps_down_to_ten_first(): void
    {
        $fact = new Fact(['operand_a' => 13, 'operand_b' => 8, 'correct_answer' => 5]);

        $this->assertSame(['13 − 3 = 10', '10 − 5 = 5'], $this->type->hint($fact));
    }

    public function test_a_no_borrow_hint_is_a_single_direct_step(): void
    {
        $fact = new Fact(['operand_a' => 9, 'operand_b' => 4, 'correct_answer' => 5]);

        $this->assertSame(['9 − 4 = 5'], $this->type->hint($fact));
    }
}
