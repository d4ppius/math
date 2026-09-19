<?php

namespace Tests\Unit;

use App\Services\Gamification\LevelCalculator;
use PHPUnit\Framework\TestCase;

class LevelCalculatorTest extends TestCase
{
    private LevelCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new LevelCalculator;
    }

    public function test_a_new_child_starts_at_level_one_with_no_progress(): void
    {
        $level = $this->calculator->forPoints(0);

        $this->assertSame(1, $level['level']);
        $this->assertSame('Rechen-Anfänger', $level['title']);
        $this->assertSame(0.0, $level['progress']);
        $this->assertSame(400, $level['points_to_next']);
        $this->assertSame(2, $level['next_level']);
        $this->assertFalse($level['is_max']);
    }

    public function test_the_level_changes_exactly_at_the_threshold(): void
    {
        $this->assertSame(1, $this->calculator->forPoints(399)['level']);
        $this->assertSame(2, $this->calculator->forPoints(400)['level']);
        $this->assertSame(2, $this->calculator->forPoints(1199)['level']);
        $this->assertSame(3, $this->calculator->forPoints(1200)['level']);
    }

    public function test_progress_and_remaining_points_within_a_level(): void
    {
        // Level 2 spans 400..1200 (800 points); 800 points is halfway.
        $level = $this->calculator->forPoints(800);

        $this->assertSame(2, $level['level']);
        $this->assertSame(0.5, $level['progress']);
        $this->assertSame(400, $level['points_to_next']);
        $this->assertSame(3, $level['next_level']);
    }

    public function test_the_top_level_is_capped_and_has_no_next_level(): void
    {
        foreach ([30000, 99999] as $points) {
            $level = $this->calculator->forPoints($points);

            $this->assertSame(10, $level['level']);
            $this->assertSame('Rechenfuchs-Meister', $level['title']);
            $this->assertTrue($level['is_max']);
            $this->assertSame(1.0, $level['progress']);
            $this->assertNull($level['points_to_next']);
            $this->assertNull($level['next_level']);
        }
    }

    public function test_negative_points_are_treated_as_zero(): void
    {
        $this->assertSame(1, $this->calculator->forPoints(-50)['level']);
    }
}
