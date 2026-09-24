<?php

namespace Tests\Unit;

use App\Models\Fact;
use App\Services\ExerciseTypes\AdditionExerciseType;
use App\Services\ExerciseTypes\MultiplicationExerciseType;
use PHPUnit\Framework\TestCase;

/**
 * Every hint is checked mechanically for every fact, not by spot check: each
 * step must be a correct binary equation, and the last one must arrive at the
 * fact's own correct_answer.
 */
class ExerciseTypeHintTest extends TestCase
{
    private function assertHintIsCorrect(array $steps, int $expectedAnswer, string $forFact): void
    {
        $this->assertNotEmpty($steps, "No hint steps for {$forFact}.");

        $result = null;
        foreach ($steps as $step) {
            $this->assertMatchesRegularExpression(
                '/^(\d+) (×|\+|−|÷) (\d+) = (\d+)$/u',
                $step,
                "Malformed hint step \"{$step}\" for {$forFact}."
            );

            preg_match('/^(\d+) (×|\+|−|÷) (\d+) = (\d+)$/u', $step, $m);
            [, $left, $op, $right, $claimed] = $m;
            [$left, $right, $claimed] = [(int) $left, (int) $right, (int) $claimed];

            $result = match ($op) {
                '×' => $left * $right,
                '+' => $left + $right,
                '−' => $left - $right,
                '÷' => $right !== 0 && $left % $right === 0 ? intdiv($left, $right) : null,
            };

            $this->assertNotNull($result, "Step \"{$step}\" for {$forFact} does not divide evenly.");
            $this->assertSame($result, $claimed, "Step \"{$step}\" for {$forFact} does not add up.");
        }

        $this->assertSame($expectedAnswer, $result, "Last step of hint for {$forFact} does not reach the correct answer.");
    }

    public function test_every_multiplication_facts_hint_is_correct(): void
    {
        $type = new MultiplicationExerciseType;

        foreach (range(1, 9) as $a) {
            foreach (range(1, 10) as $b) {
                $fact = new Fact(['operand_a' => $a, 'operand_b' => $b, 'correct_answer' => $a * $b]);

                $this->assertHintIsCorrect($type->hint($fact), $a * $b, "{$a} × {$b}");
            }
        }
    }

    public function test_every_addition_facts_hint_is_correct(): void
    {
        $type = new AdditionExerciseType;

        foreach (range(1, 10) as $a) {
            foreach (range(1, 10) as $b) {
                $fact = new Fact(['operand_a' => $a, 'operand_b' => $b, 'correct_answer' => $a + $b]);

                $this->assertHintIsCorrect($type->hint($fact), $a + $b, "{$a} + {$b}");
            }
        }
    }
}
