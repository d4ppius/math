<?php

namespace Tests\Feature;

use App\Models\Badge;
use App\Models\Child;
use App\Models\ExerciseType;
use App\Models\PracticeSession;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SummaryConfettiTest extends TestCase
{
    use RefreshDatabase;

    private function summaryFor(Child $child, int $correct): TestResponse
    {
        $session = PracticeSession::create([
            'child_id' => $child->id,
            'exercise_type_id' => ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins'])->id,
            'started_at' => now()->subMinutes(5),
            'planned_duration_seconds' => 600,
            'status' => 'completed',
            'questions_answered' => 8,
            'questions_correct' => $correct,
        ]);

        return $this->actingAs($child, 'child')->get(route('child.sessions.summary', $session));
    }

    public function test_a_session_with_correct_answers_celebrates_with_a_small_burst(): void
    {
        $this->summaryFor(Child::factory()->create(), 6)
            ->assertOk()
            ->assertSee('celebrate({ big: false })', false);
    }

    public function test_a_new_badge_triggers_the_bigger_celebration(): void
    {
        $this->seed(BadgeSeeder::class);
        $child = Child::factory()->create();
        $child->badges()->attach(Badge::where('key', 'blitz')->first()->id, ['earned_at' => now()]);

        $this->summaryFor($child, 6)->assertSee('celebrate({ big: true })', false);
    }

    public function test_a_session_without_a_single_correct_answer_has_no_confetti(): void
    {
        $this->summaryFor(Child::factory()->create(), 0)
            ->assertOk()
            ->assertDontSee('celebrate(', false);
    }
}
