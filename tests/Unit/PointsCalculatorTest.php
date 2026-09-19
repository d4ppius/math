<?php

namespace Tests\Unit;

use App\Services\Gamification\PointsCalculator;
use PHPUnit\Framework\TestCase;

class PointsCalculatorTest extends TestCase
{
    private PointsCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new PointsCalculator;
    }

    public function test_a_wrong_answer_earns_nothing(): void
    {
        $this->assertSame(0, $this->calculator->forAnswer(false, 100, 3500, 3));
    }

    public function test_the_speed_bonus_scales_with_how_far_the_target_time_is_beaten(): void
    {
        $this->assertSame(20, $this->calculator->forAnswer(true, 0, 3500, 1));
        $this->assertSame(15, $this->calculator->forAnswer(true, 1750, 3500, 1));
        $this->assertSame(10, $this->calculator->forAnswer(true, 3500, 3500, 1));
    }

    public function test_a_slow_correct_answer_never_earns_less_than_the_base_points(): void
    {
        $this->assertSame(10, $this->calculator->forAnswer(true, 5000, 3500, 1));
        $this->assertSame(10, $this->calculator->forAnswer(true, 7000, 3500, 1));
        $this->assertSame(10, $this->calculator->forAnswer(true, 60000, 3500, 1));
    }

    public function test_the_session_streak_multiplies_the_points(): void
    {
        $this->assertSame(10, $this->calculator->forAnswer(true, 3500, 3500, 4));
        $this->assertSame(12, $this->calculator->forAnswer(true, 3500, 3500, 5));
        $this->assertSame(15, $this->calculator->forAnswer(true, 3500, 3500, 10));
        $this->assertSame(30, $this->calculator->forAnswer(true, 0, 3500, 10));
    }

    public function test_with_the_speed_bonus_switched_off_every_correct_answer_earns_the_same(): void
    {
        $this->assertSame(10, $this->calculator->forAnswer(true, 0, 3500, 1, speedBonusEnabled: false));
        $this->assertSame(10, $this->calculator->forAnswer(true, 60000, 3500, 1, speedBonusEnabled: false));
        // The streak multiplier still applies.
        $this->assertSame(12, $this->calculator->forAnswer(true, 0, 3500, 5, speedBonusEnabled: false));
        $this->assertSame(0, $this->calculator->forAnswer(false, 0, 3500, 5, speedBonusEnabled: false));
    }
}
