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

    public function test_the_countdown_is_hidden_from_the_child_when_the_timer_is_switched_off(): void
    {
        $child = $this->makeReadyChild();
        $this->actingAs($child, 'child')->post(route('child.sessions.start'));
        $session = PracticeSession::first();

        $this->actingAs($child, 'child')
            ->get(route('child.sessions.show', $session))
            ->assertOk()
            ->assertSee('⏱')
            ->assertDontSee('Gleich geschafft');

        $child->exerciseSettings()->update(['show_timer' => false]);

        $this->actingAs($child, 'child')
            ->get(route('child.sessions.show', $session))
            ->assertOk()
            ->assertDontSee('⏱')
            ->assertSee('Gleich geschafft')
            // The countdown itself keeps running in the background.
            ->assertSee('timeRemaining');
    }

    public function test_the_practice_page_offers_to_retype_a_wrong_answer_instead_of_auto_advancing(): void
    {
        $child = $this->makeReadyChild();
        $this->actingAs($child, 'child')->post(route('child.sessions.start'));
        $session = PracticeSession::first();

        $this->actingAs($child, 'child')
            ->get(route('child.sessions.show', $session))
            ->assertOk()
            ->assertSee('Jetzt du: Tippe die Lösung ein.')
            // A correct answer keeps advancing on its own; only a wrong one waits for a retype.
            ->assertSee('data.is_correct', false)
            ->assertSee('correcting = true', false);
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

    public function test_the_practice_screen_plays_sounds_unless_a_parent_switched_them_off(): void
    {
        $child = $this->makeReadyChild();
        $this->actingAs($child, 'child')->post(route('child.sessions.start'));
        $session = PracticeSession::first();

        $this->actingAs($child, 'child')
            ->get(route('child.sessions.show', $session))
            ->assertOk()
            ->assertSee('soundEnabled: true', false)
            ->assertSee('playFeedbackSound', false);

        $child->exerciseSettings()->update(['sound_enabled' => false]);

        $this->actingAs($child, 'child')
            ->get(route('child.sessions.show', $session))
            ->assertSee('soundEnabled: false', false);
    }

    public function test_a_slow_correct_answer_still_earns_the_base_points(): void
    {
        Carbon::setTestNow(now());

        $child = $this->makeReadyChild();
        $this->actingAs($child, 'child')->post(route('child.sessions.start'));
        $session = PracticeSession::first();

        $this->actingAs($child, 'child')->getJson(route('child.sessions.next-question', $session));
        $fact = $session->refresh()->currentFact;

        Carbon::setTestNow(now()->addSeconds(30));

        $response = $this->actingAs($child, 'child')->postJson(route('child.sessions.attempts', $session), [
            'answer' => $fact->correct_answer,
        ]);

        $this->assertSame(10, $response->json('points_awarded'));

        Carbon::setTestNow();
    }

    public function test_the_speed_bonus_can_be_switched_off_per_child(): void
    {
        $child = $this->makeReadyChild();
        $child->exerciseSettings()->update(['speed_bonus_enabled' => false]);
        $this->actingAs($child, 'child')->post(route('child.sessions.start'));
        $session = PracticeSession::first();

        $this->actingAs($child, 'child')->getJson(route('child.sessions.next-question', $session));
        $fact = $session->refresh()->currentFact;

        // Answered instantly, which would normally earn the full speed bonus.
        $response = $this->actingAs($child, 'child')->postJson(route('child.sessions.attempts', $session), [
            'answer' => $fact->correct_answer,
        ]);

        $this->assertSame(10, $response->json('points_awarded'));
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

        // correct_answer lets the child retype the right answer client-side afterwards.
        $response->assertOk()->assertJson(['is_correct' => false, 'points_awarded' => 0, 'correct_answer' => $fact->correct_answer]);

        $stat = $child->factStats()->where('fact_id', $fact->id)->first();
        $this->assertSame(0, $stat->current_streak);
        $this->assertSame(0, $child->refresh()->total_points);
    }

    public function test_a_wrong_answer_comes_with_a_hint_but_a_correct_one_does_not(): void
    {
        $child = $this->makeReadyChild();
        $this->actingAs($child, 'child')->post(route('child.sessions.start'));
        $session = PracticeSession::first();

        $this->actingAs($child, 'child')->getJson(route('child.sessions.next-question', $session));
        $fact = $session->refresh()->currentFact;

        $wrong = $this->actingAs($child, 'child')->postJson(route('child.sessions.attempts', $session), [
            'answer' => $fact->correct_answer + 1,
        ]);
        $this->assertIsArray($wrong->json('hint'));
        $this->assertNotEmpty($wrong->json('hint'));

        $this->actingAs($child, 'child')->getJson(route('child.sessions.next-question', $session));
        $fact = $session->refresh()->currentFact;

        $correct = $this->actingAs($child, 'child')->postJson(route('child.sessions.attempts', $session), [
            'answer' => $fact->correct_answer,
        ]);
        $this->assertNull($correct->json('hint'));
    }

    public function test_the_hint_is_only_shown_on_the_practice_page_when_switched_on(): void
    {
        $child = $this->makeReadyChild();
        $this->actingAs($child, 'child')->post(route('child.sessions.start'));
        $session = PracticeSession::first();

        $this->actingAs($child, 'child')
            ->get(route('child.sessions.show', $session))
            ->assertOk()
            ->assertDontSee('💡');

        $child->exerciseSettings()->update(['show_hints' => true]);

        $this->actingAs($child, 'child')
            ->get(route('child.sessions.show', $session))
            ->assertOk()
            ->assertSee('💡');
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
