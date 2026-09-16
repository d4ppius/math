<?php

namespace Tests\Unit;

use App\Models\ChildFactStat;
use App\Services\AdaptiveSelection\FactPriorityCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactPriorityCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_fact_with_no_history_gets_the_maximum_default_weight(): void
    {
        $calculator = new FactPriorityCalculator;

        $this->assertEqualsWithDelta(1.05, $calculator->calculate(null, 3000), 0.001);
    }

    public function test_a_fact_with_a_higher_error_rate_gets_a_higher_priority(): void
    {
        $calculator = new FactPriorityCalculator;

        $mostlyWrong = ChildFactStat::factory()->make([
            'attempts_total' => 10,
            'attempts_correct' => 1,
            'avg_response_ms' => 3000,
            'last_practiced_at' => now(),
        ]);

        $mostlyCorrect = ChildFactStat::factory()->make([
            'attempts_total' => 10,
            'attempts_correct' => 9,
            'avg_response_ms' => 3000,
            'last_practiced_at' => now(),
        ]);

        $this->assertGreaterThan(
            $calculator->calculate($mostlyCorrect, 3000),
            $calculator->calculate($mostlyWrong, 3000),
        );
    }

    public function test_a_slower_than_target_response_increases_priority(): void
    {
        $calculator = new FactPriorityCalculator;

        $slow = ChildFactStat::factory()->make([
            'attempts_total' => 5,
            'attempts_correct' => 5,
            'avg_response_ms' => 9000,
            'last_practiced_at' => now(),
        ]);

        $fast = ChildFactStat::factory()->make([
            'attempts_total' => 5,
            'attempts_correct' => 5,
            'avg_response_ms' => 500,
            'last_practiced_at' => now(),
        ]);

        $this->assertGreaterThan(
            $calculator->calculate($fast, 3000),
            $calculator->calculate($slow, 3000),
        );
    }

    public function test_a_fact_not_practiced_in_a_while_increases_priority(): void
    {
        $calculator = new FactPriorityCalculator;

        $stale = ChildFactStat::factory()->make([
            'attempts_total' => 5,
            'attempts_correct' => 5,
            'avg_response_ms' => 3000,
            'last_practiced_at' => now()->subDays(30),
        ]);

        $fresh = ChildFactStat::factory()->make([
            'attempts_total' => 5,
            'attempts_correct' => 5,
            'avg_response_ms' => 3000,
            'last_practiced_at' => now(),
        ]);

        $this->assertGreaterThan(
            $calculator->calculate($fresh, 3000),
            $calculator->calculate($stale, 3000),
        );
    }
}
