<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\ExerciseType;
use App\Models\Family;
use App\Models\PracticeSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticsChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_weekly_bars_are_sized_relative_to_the_best_day_inside_a_track_that_fills_its_column(): void
    {
        $family = Family::factory()->create();
        $user = User::factory()->for($family)->create();
        $child = Child::factory()->for($family)->create();
        $type = ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins']);

        foreach ([0 => 400, 1 => 200] as $daysAgo => $points) {
            PracticeSession::create([
                'child_id' => $child->id, 'exercise_type_id' => $type->id, 'started_at' => now()->subDays($daysAgo)->setTime(12, 0),
                'planned_duration_seconds' => 600, 'status' => 'completed', 'total_points' => $points,
            ]);
        }

        $html = $this->actingAs($user)->get(route('parent.children.statistics', $child))->assertOk()->getContent();

        // Best day fills the track, half the points give half the height.
        $this->assertStringContainsString('style="height: 100%;"', $html);
        $this->assertStringContainsString('style="height: 50%;"', $html);
        // The track is a flex-1 child of a full-height column, not "height: 100%" of an auto-height parent
        // (that collapsed to zero and made the bars invisible).
        $this->assertStringContainsString('h-full', $html);
        $this->assertStringNotContainsString('bg-orange-100 rounded-t-md flex items-end" style="height: 100%;"', $html);
    }

    public function test_a_preview_sessions_points_do_not_count_towards_the_weekly_chart(): void
    {
        $family = Family::factory()->create();
        $user = User::factory()->for($family)->create();
        $child = Child::factory()->for($family)->create();
        $type = ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins']);

        PracticeSession::create([
            'child_id' => $child->id, 'exercise_type_id' => $type->id, 'started_at' => now()->setTime(12, 0),
            'planned_duration_seconds' => 600, 'status' => 'completed', 'total_points' => 500, 'is_preview' => true,
        ]);

        $html = $this->actingAs($user)->get(route('parent.children.statistics', $child))->assertOk()->getContent();

        // Without any real points, every bar sits at the chart's floor (4%), never at 100%.
        $this->assertStringNotContainsString('style="height: 100%;"', $html);
    }
}
