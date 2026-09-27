<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\ExerciseType;
use App\Models\Family;
use App\Models\PracticeSession;
use App\Models\User;
use App\Notifications\DailyGoalAchieved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * A session nobody ever comes back to (the child closes the app mid-session
 * and never reopens it) otherwise stays "active" forever: PracticeSession's
 * status only ever advances on the next request touching it. See
 * app/Console/Commands/CloseExpiredSessions.php.
 */
class CloseExpiredSessionsTest extends TestCase
{
    use RefreshDatabase;

    private function expiredSession(Child $child, array $overrides = []): PracticeSession
    {
        $type = ExerciseType::first() ?? ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins']);

        return PracticeSession::create($overrides + [
            'child_id' => $child->id, 'exercise_type_id' => $type->id,
            'started_at' => now()->subMinutes(20), 'planned_duration_seconds' => 600, // ran out 10 minutes ago
            'status' => 'active', 'questions_answered' => 0, 'questions_correct' => 0,
        ]);
    }

    public function test_it_closes_a_session_whose_time_has_run_out(): void
    {
        $child = Child::factory()->create();
        $session = $this->expiredSession($child, ['questions_answered' => 15, 'questions_correct' => 12]);

        $this->artisan('sessions:close-expired')->assertSuccessful();

        $session->refresh();
        $this->assertSame('completed', $session->status);
        $this->assertNotNull($session->ended_at);
    }

    public function test_it_leaves_a_session_with_time_remaining_untouched(): void
    {
        $child = Child::factory()->create();
        $type = ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins']);
        $session = PracticeSession::create([
            'child_id' => $child->id, 'exercise_type_id' => $type->id,
            'started_at' => now(), 'planned_duration_seconds' => 600, 'status' => 'active',
        ]);

        $this->artisan('sessions:close-expired');

        $this->assertSame('active', $session->refresh()->status);
    }

    public function test_it_does_not_reprocess_an_already_completed_session(): void
    {
        Notification::fake();
        $family = Family::factory()->create();
        User::factory()->for($family)->create();
        $child = Child::factory()->for($family)->create();
        $type = ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins']);
        PracticeSession::create([
            'child_id' => $child->id, 'exercise_type_id' => $type->id,
            'started_at' => now()->subMinutes(20), 'planned_duration_seconds' => 600,
            'status' => 'completed', 'ended_at' => now()->subMinutes(15), 'questions_answered' => 12,
        ]);

        $this->artisan('sessions:close-expired');

        Notification::assertNothingSent();
    }

    public function test_closing_an_expired_session_notifies_parents_if_the_daily_goal_was_met(): void
    {
        Notification::fake();
        $family = Family::factory()->create();
        $parent = User::factory()->for($family)->create();
        $child = Child::factory()->for($family)->create();
        $this->expiredSession($child, ['questions_answered' => 12, 'questions_correct' => 10]);

        $this->artisan('sessions:close-expired');

        Notification::assertSentTo($parent, DailyGoalAchieved::class);
    }

    public function test_closing_an_expired_session_with_too_few_answers_notifies_nobody(): void
    {
        Notification::fake();
        $family = Family::factory()->create();
        User::factory()->for($family)->create();
        $child = Child::factory()->for($family)->create();
        $this->expiredSession($child, ['questions_answered' => 3, 'questions_correct' => 3]);

        $this->artisan('sessions:close-expired');

        Notification::assertNothingSent();
    }

    public function test_an_expired_preview_session_is_closed_but_triggers_no_notification(): void
    {
        Notification::fake();
        $family = Family::factory()->create();
        User::factory()->for($family)->create();
        $child = Child::factory()->for($family)->create();
        $session = $this->expiredSession($child, ['questions_answered' => 15, 'questions_correct' => 15, 'is_preview' => true]);

        $this->artisan('sessions:close-expired');

        $this->assertSame('completed', $session->refresh()->status);
        Notification::assertNothingSent();
    }
}
