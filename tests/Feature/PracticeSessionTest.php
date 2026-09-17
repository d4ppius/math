<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\ChildExerciseSetting;
use App\Models\DailyGoalLog;
use App\Models\ExerciseType;
use App\Models\Fact;
use App\Models\PracticeSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PracticeSessionTest extends TestCase
{
    use RefreshDatabase;

    private function makeReadyChild(): Child
    {
        $exerciseType = ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins']);

        foreach (range(1, 2) as $a) {
            foreach (range(1, 10) as $b) {
                Fact::create([
                    'exercise_type_id' => $exerciseType->id,
                    'operand_a' => $a,
                    'operand_b' => $b,
                    'correct_answer' => $a * $b,
                    'difficulty_group' => $a,
                ]);
            }
        }

        $child = Child::factory()->create();

        ChildExerciseSetting::create([
            'child_id' => $child->id,
            'exercise_type_id' => $exerciseType->id,
            'active_groups' => [1, 2],
            'session_duration_minutes' => 10,
            'target_frequency' => 'daily',
        ]);

        return $child;
    }

    public function test_a_child_can_start_a_session_and_get_a_question_from_the_active_groups(): void
    {
        $child = $this->makeReadyChild();

        $this->actingAs($child, 'child')
            ->post(route('child.sessions.start'))
            ->assertRedirect();

        $session = PracticeSession::first();
        $this->assertNotNull($session);
        $this->assertSame('active', $session->status);

        $response = $this->actingAs($child, 'child')
            ->getJson(route('child.sessions.next-question', $session));

        $response->assertOk()->assertJsonStructure(['prompt', 'time_remaining_seconds']);
        $this->assertNotNull($session->refresh()->current_fact_id);
    }

    public function test_a_correct_answer_awards_points_and_updates_fact_stats(): void
    {
        $child = $this->makeReadyChild();
        $this->actingAs($child, 'child')->post(route('child.sessions.start'));
        $session = PracticeSession::first();

        $this->actingAs($child, 'child')->getJson(route('child.sessions.next-question', $session));
        $fact = $session->refresh()->currentFact;

        $response = $this->actingAs($child, 'child')->postJson(route('child.sessions.attempts', $session), [
            'answer' => $fact->correct_answer,
        ]);

        $response->assertOk()->assertJson(['is_correct' => true]);
        $this->assertGreaterThan(0, $response->json('points_awarded'));

        $stat = $child->factStats()->where('fact_id', $fact->id)->first();
        $this->assertSame(1, $stat->attempts_total);
        $this->assertSame(1, $stat->attempts_correct);

        $this->assertSame($response->json('points_awarded'), $child->refresh()->total_points);
    }

    public function test_a_wrong_answer_awards_no_points_and_resets_the_streak(): void
    {
        $child = $this->makeReadyChild();
        $this->actingAs($child, 'child')->post(route('child.sessions.start'));
        $session = PracticeSession::first();

        $this->actingAs($child, 'child')->getJson(route('child.sessions.next-question', $session));
        $fact = $session->refresh()->currentFact;

        $response = $this->actingAs($child, 'child')->postJson(route('child.sessions.attempts', $session), [
            'answer' => $fact->correct_answer + 1,
        ]);

        $response->assertOk()->assertJson(['is_correct' => false, 'points_awarded' => 0]);

        $stat = $child->factStats()->where('fact_id', $fact->id)->first();
        $this->assertSame(0, $stat->current_streak);
        $this->assertSame(0, $child->refresh()->total_points);
    }

    public function test_the_session_ends_once_its_time_budget_is_used_up(): void
    {
        Carbon::setTestNow(now());

        $child = $this->makeReadyChild();
        $this->actingAs($child, 'child')->post(route('child.sessions.start'));
        $session = PracticeSession::first();

        Carbon::setTestNow(now()->addMinutes(11));

        $response = $this->actingAs($child, 'child')->getJson(route('child.sessions.next-question', $session));

        $response->assertOk()->assertJson(['session_over' => true]);
        $this->assertSame('completed', $session->refresh()->status);

        Carbon::setTestNow();
    }

    public function test_time_remaining_stays_sane_even_if_started_at_drifts_into_the_future(): void
    {
        // Regression test: a clock/timezone skew between the request that
        // created the session and the one reading it back must never turn
        // "planned - elapsed" into a huge bogus countdown (see
        // PracticeSessionController::timeRemainingSeconds).
        $child = $this->makeReadyChild();
        $this->actingAs($child, 'child')->post(route('child.sessions.start'));
        $session = PracticeSession::first();

        $session->forceFill(['started_at' => now()->addHours(2)])->save();

        $response = $this->actingAs($child, 'child')
            ->getJson(route('child.sessions.next-question', $session));

        $response->assertOk();
        $this->assertLessThanOrEqual(600, $response->json('time_remaining_seconds'));
    }

    public function test_a_child_can_manually_finish_a_session_early(): void
    {
        $child = $this->makeReadyChild();
        $this->actingAs($child, 'child')->post(route('child.sessions.start'));
        $session = PracticeSession::first();

        $this->actingAs($child, 'child')
            ->post(route('child.sessions.finish', $session))
            ->assertRedirect(route('child.sessions.summary', $session));

        $this->assertSame('completed', $session->refresh()->status);
        $this->assertNotNull($session->ended_at);
    }

    public function test_finishing_a_session_with_enough_answers_logs_the_daily_goal_as_met(): void
    {
        $child = $this->makeReadyChild();
        $this->actingAs($child, 'child')->post(route('child.sessions.start'));
        $session = PracticeSession::first();

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($child, 'child')->getJson(route('child.sessions.next-question', $session));
            $fact = $session->refresh()->currentFact;
            $this->actingAs($child, 'child')->postJson(route('child.sessions.attempts', $session), [
                'answer' => $fact->correct_answer,
            ]);
        }

        $this->actingAs($child, 'child')
            ->post(route('child.sessions.finish', $session))
            ->assertRedirect(route('child.sessions.summary', $session));

        $log = DailyGoalLog::where('child_id', $child->id)->first();
        $this->assertNotNull($log);
        $this->assertTrue($log->goal_met);
    }

    public function test_a_child_cannot_access_another_childs_session(): void
    {
        $child = $this->makeReadyChild();
        $otherChild = Child::factory()->for($child->family)->create();

        $this->actingAs($child, 'child')->post(route('child.sessions.start'));
        $session = PracticeSession::first();

        $this->actingAs($otherChild, 'child')
            ->get(route('child.sessions.show', $session))
            ->assertForbidden();
    }
}
