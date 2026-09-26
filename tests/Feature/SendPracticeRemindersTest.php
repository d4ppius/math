<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\ChildExerciseSetting;
use App\Models\DailyGoalLog;
use App\Models\ExerciseType;
use App\Notifications\PracticeReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendPracticeRemindersTest extends TestCase
{
    use RefreshDatabase;

    private function subscribedChild(): Child
    {
        $child = Child::factory()->create(['active' => true]);
        $child->updatePushSubscription('https://push.example.com/'.$child->id, 'p256dh', 'auth');

        return $child;
    }

    public function test_it_reminds_a_subscribed_child_who_has_not_practiced_today(): void
    {
        Notification::fake();
        $this->travelTo(now()->setTime(17, 0)); // the default reminder_time

        $exerciseType = ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins']);
        $child = $this->subscribedChild();
        ChildExerciseSetting::create([
            'child_id' => $child->id,
            'exercise_type_id' => $exerciseType->id,
            'active_groups' => [1],
            'session_duration_minutes' => 10,
            'target_frequency' => 'daily',
        ]);

        $this->artisan('reminders:send')->assertSuccessful();

        Notification::assertSentTo($child, PracticeReminder::class);
        $this->assertSame(now()->toDateString(), $child->fresh()->reminded_at->toDateString());
    }

    public function test_it_skips_a_child_who_already_met_todays_goal(): void
    {
        Notification::fake();
        $this->travelTo(now()->setTime(17, 0));

        $exerciseType = ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins']);
        $child = $this->subscribedChild();
        ChildExerciseSetting::create([
            'child_id' => $child->id,
            'exercise_type_id' => $exerciseType->id,
            'active_groups' => [1],
            'session_duration_minutes' => 10,
            'target_frequency' => 'daily',
        ]);
        DailyGoalLog::create(['child_id' => $child->id, 'date' => now()->toDateString(), 'goal_met' => true]);

        $this->artisan('reminders:send');

        Notification::assertNotSentTo($child, PracticeReminder::class);
    }

    public function test_it_skips_a_child_without_a_push_subscription(): void
    {
        Notification::fake();
        $this->travelTo(now()->setTime(17, 0));

        $exerciseType = ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins']);
        $child = Child::factory()->create(['active' => true]);
        ChildExerciseSetting::create([
            'child_id' => $child->id,
            'exercise_type_id' => $exerciseType->id,
            'active_groups' => [1],
            'session_duration_minutes' => 10,
            'target_frequency' => 'daily',
        ]);

        $this->artisan('reminders:send');

        Notification::assertNothingSent();
    }

    public function test_it_skips_a_child_whose_settings_do_not_target_today_on_a_weekend(): void
    {
        Notification::fake();
        $this->travelTo(now()->startOfWeek()->addDays(5)->setTime(17, 0)); // Saturday

        $exerciseType = ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins']);
        $child = $this->subscribedChild();
        ChildExerciseSetting::create([
            'child_id' => $child->id,
            'exercise_type_id' => $exerciseType->id,
            'active_groups' => [1],
            'session_duration_minutes' => 10,
            'target_frequency' => 'weekdays',
        ]);

        $this->artisan('reminders:send');

        Notification::assertNothingSent();
    }

    public function test_it_skips_a_child_whose_parents_switched_reminders_off(): void
    {
        Notification::fake();
        $this->travelTo(now()->setTime(17, 0));

        $exerciseType = ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins']);
        $child = $this->subscribedChild();
        $child->update(['reminders_enabled' => false]);
        ChildExerciseSetting::create([
            'child_id' => $child->id,
            'exercise_type_id' => $exerciseType->id,
            'active_groups' => [1],
            'session_duration_minutes' => 10,
            'target_frequency' => 'daily',
        ]);

        $this->artisan('reminders:send');

        Notification::assertNothingSent();
    }

    public function test_it_only_reminds_within_the_five_minute_window_of_the_childs_own_time(): void
    {
        Notification::fake();

        $exerciseType = ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins']);
        $child = $this->subscribedChild();
        $child->update(['reminder_time' => '08:30:00']);
        ChildExerciseSetting::create([
            'child_id' => $child->id,
            'exercise_type_id' => $exerciseType->id,
            'active_groups' => [1],
            'session_duration_minutes' => 10,
            'target_frequency' => 'daily',
        ]);

        $this->travelTo(now()->setTime(8, 25));
        $this->artisan('reminders:send');
        Notification::assertNothingSent();

        $this->travelTo(now()->setTime(8, 32));
        $this->artisan('reminders:send');
        Notification::assertSentTo($child, PracticeReminder::class);
    }

    public function test_a_reminder_right_before_midnight_still_fires_once_the_date_rolls_over(): void
    {
        Notification::fake();

        $exerciseType = ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins']);
        $child = $this->subscribedChild();
        $child->update(['reminder_time' => '23:58:00']);
        ChildExerciseSetting::create([
            'child_id' => $child->id,
            'exercise_type_id' => $exerciseType->id,
            'active_groups' => [1],
            'session_duration_minutes' => 10,
            'target_frequency' => 'daily',
        ]);

        // The scheduler's every-five-minutes ticks land on the hour/5, e.g.
        // 23:55 and 00:00 — the second one must still catch a 23:58 target.
        $this->travelTo(now()->startOfDay()->addHours(23)->addMinutes(55));
        $this->artisan('reminders:send');
        Notification::assertNothingSent();

        $this->travelTo(now()->addMinutes(5));
        $this->artisan('reminders:send');
        Notification::assertSentTo($child, PracticeReminder::class);
    }

    public function test_it_does_not_remind_the_same_child_twice_in_the_same_window(): void
    {
        Notification::fake();
        $this->travelTo(now()->setTime(17, 0));

        $exerciseType = ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins']);
        $child = $this->subscribedChild();
        ChildExerciseSetting::create([
            'child_id' => $child->id,
            'exercise_type_id' => $exerciseType->id,
            'active_groups' => [1],
            'session_duration_minutes' => 10,
            'target_frequency' => 'daily',
        ]);

        $this->artisan('reminders:send');
        $this->travelTo(now()->addMinutes(3)); // still inside the same 5-minute window
        $this->artisan('reminders:send');

        Notification::assertSentToTimes($child, PracticeReminder::class, 1);
    }
}
