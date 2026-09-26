<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\ChildExerciseSetting;
use App\Models\ChildFactStat;
use App\Models\DailyGoalLog;
use App\Models\ExerciseType;
use App\Models\Fact;
use App\Models\Family;
use App\Models\PracticeSession;
use App\Models\SessionAttempt;
use App\Models\User;
use App\Notifications\DailyGoalAchieved;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * A preview session (started via ChildPreviewController while an admin or
 * parent looks through the child's own eyes) must behave normally on
 * screen but leave no trace in real progress data. See
 * docs/plan-kindsicht-vorschau.md step 2.
 */
class ChildPreviewSessionTest extends TestCase
{
    use RefreshDatabase;

    private function makeReadyChild(): Child
    {
        $exerciseType = ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins']);

        foreach (range(1, 10) as $b) {
            Fact::create([
                'exercise_type_id' => $exerciseType->id,
                'operand_a' => 1,
                'operand_b' => $b,
                'correct_answer' => $b,
                'difficulty_group' => 1,
            ]);
        }

        $family = Family::factory()->create();
        User::factory()->for($family)->create();
        $child = Child::factory()->for($family)->create();

        ChildExerciseSetting::create([
            'child_id' => $child->id,
            'exercise_type_id' => $exerciseType->id,
            'active_groups' => [1],
            'session_duration_minutes' => 10,
            'target_frequency' => 'daily',
        ]);

        return $child;
    }

    /** Logs the child's own parent in and starts a preview of this child. */
    private function startPreview(Child $child): void
    {
        $parent = $child->family->users->firstOrFail();

        // Guard spelled out explicitly: an earlier actingAs($child, 'child')
        // elsewhere in a test would otherwise have quietly made this log the
        // parent into the wrong guard (see ChildPreviewTest for the same gotcha).
        $this->actingAs($parent, 'web')->post(route('child-preview.start', $child))->assertRedirect(route('child.home'));
    }

    private function answerQuestions(PracticeSession $session, int $count, bool $correctly = true): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->getJson(route('child.sessions.next-question', $session));
            $fact = $session->refresh()->currentFact;

            $this->postJson(route('child.sessions.attempts', $session), [
                'answer' => $correctly ? $fact->correct_answer : $fact->correct_answer + 1,
            ]);
        }
    }

    public function test_starting_a_session_during_a_preview_never_resumes_the_childs_real_one(): void
    {
        $child = $this->makeReadyChild();
        $this->actingAs($child, 'child')->post(route('child.sessions.start'));
        $realSession = PracticeSession::firstOrFail();

        $this->startPreview($child);
        $this->post(route('child.sessions.start'));

        $this->assertSame(2, PracticeSession::count(), 'a second, separate session was created');
        $previewSession = PracticeSession::where('id', '!=', $realSession->id)->firstOrFail();

        $this->assertFalse($realSession->refresh()->is_preview);
        $this->assertTrue($previewSession->is_preview);
        $this->assertSame('active', $realSession->status, 'untouched by the preview');
    }

    public function test_a_leftover_active_preview_never_offers_to_resume_on_the_childs_own_home_screen(): void
    {
        $child = $this->makeReadyChild();
        $this->startPreview($child);
        $this->post(route('child.sessions.start')); // left running, never finished

        $this->assertTrue($child->fresh()->resumableSessionFor($child->exerciseSettings()->first()->exercise_type_id) === null);

        $this->actingAs($child, 'child')
            ->get(route('child.home'))
            ->assertOk()
            ->assertDontSee('Weiter üben');
    }

    public function test_answering_in_a_preview_changes_nothing_but_still_looks_real(): void
    {
        $child = $this->makeReadyChild();
        $this->startPreview($child);

        $this->post(route('child.sessions.start'));
        $session = PracticeSession::firstOrFail();
        $this->assertTrue($session->is_preview);

        $this->getJson(route('child.sessions.next-question', $session));
        $fact = $session->refresh()->currentFact;

        $response = $this->postJson(route('child.sessions.attempts', $session), ['answer' => $fact->correct_answer]);

        $response->assertOk()->assertJson(['is_correct' => true]);
        $this->assertGreaterThan(0, $response->json('points_awarded'));

        $this->assertSame(0, ChildFactStat::count());
        $this->assertSame(0, SessionAttempt::count());
        $this->assertSame(0, $child->refresh()->total_points);
        $this->assertSame(0, $child->exerciseSettings()->first()->points);

        // The preview session's own row still tracks what happened, for its
        // own summary page — it just doesn't reach anywhere else.
        $this->assertSame(1, $session->refresh()->questions_answered);
        $this->assertGreaterThan(0, $session->total_points);
    }

    public function test_a_completed_preview_session_earns_no_badges_and_no_daily_goal(): void
    {
        $this->seed(BadgeSeeder::class);
        Notification::fake();

        $child = $this->makeReadyChild();
        $this->startPreview($child);

        $this->post(route('child.sessions.start'));
        $session = PracticeSession::firstOrFail();

        $this->answerQuestions($session, 10);

        $this->post(route('child.sessions.finish', $session))
            ->assertRedirect(route('child.sessions.summary', $session));

        $this->assertSame(0, $child->badges()->count());
        $this->assertSame(0, DailyGoalLog::count());
        Notification::assertNotSentTo($child->family->users->first(), DailyGoalAchieved::class);
    }
}
