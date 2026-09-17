<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\ChildExerciseSetting;
use App\Models\ExerciseType;
use App\Models\Fact;
use App\Models\PracticeSession;
use App\Models\User;
use App\Notifications\DailyGoalAchieved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DailyGoalNotificationTest extends TestCase
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

        $child = Child::factory()->create();

        ChildExerciseSetting::create([
            'child_id' => $child->id,
            'exercise_type_id' => $exerciseType->id,
            'active_groups' => [1],
            'session_duration_minutes' => 10,
            'target_frequency' => 'daily',
        ]);

        return $child;
    }

    private function completeASessionWithAnswers(Child $child, int $answers): PracticeSession
    {
        $this->actingAs($child, 'child')->post(route('child.sessions.start'));
        $session = PracticeSession::whereChildId($child->id)->latest('id')->first();

        for ($i = 0; $i < $answers; $i++) {
            $this->actingAs($child, 'child')->getJson(route('child.sessions.next-question', $session));
            $fact = $session->refresh()->currentFact;
            $this->actingAs($child, 'child')->postJson(route('child.sessions.attempts', $session), [
                'answer' => $fact->correct_answer,
            ]);
        }

        // assertRedirect (not just "did something happen") so a 500 from
        // e.g. a duplicate-listener or date-matching bug in the
        // PracticeSessionCompleted pipeline can never hide behind a
        // notification-count assertion again.
        $this->actingAs($child, 'child')
            ->post(route('child.sessions.finish', $session))
            ->assertRedirect(route('child.sessions.summary', $session));

        return $session->refresh();
    }

    public function test_reaching_the_daily_goal_notifies_every_parent_in_the_family(): void
    {
        Notification::fake();

        $child = $this->makeReadyChild();
        User::factory()->for($child->family)->create();
        User::factory()->for($child->family)->create();

        $this->completeASessionWithAnswers($child, 10);

        Notification::assertSentTimes(DailyGoalAchieved::class, 2);
    }

    public function test_the_notification_is_not_sent_twice_for_the_same_day(): void
    {
        Notification::fake();

        $child = $this->makeReadyChild();
        User::factory()->for($child->family)->create();

        $this->completeASessionWithAnswers($child, 10);
        $this->completeASessionWithAnswers($child, 10);

        Notification::assertSentTimes(DailyGoalAchieved::class, 1);
    }

    public function test_completing_several_sessions_the_same_day_never_errors(): void
    {
        Notification::fake();

        $child = $this->makeReadyChild();
        User::factory()->for($child->family)->create();

        $this->completeASessionWithAnswers($child, 10);
        $this->completeASessionWithAnswers($child, 10);
        $this->completeASessionWithAnswers($child, 10);

        Notification::assertSentTimes(DailyGoalAchieved::class, 1);
    }

    public function test_no_notification_when_the_goal_is_not_reached(): void
    {
        Notification::fake();

        $child = $this->makeReadyChild();
        User::factory()->for($child->family)->create();

        $this->completeASessionWithAnswers($child, 3);

        Notification::assertNothingSent();
    }
}
