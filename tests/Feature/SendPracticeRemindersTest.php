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
    }

    public function test_it_skips_a_child_who_already_met_todays_goal(): void
    {
        Notification::fake();

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
        $this->travelTo(now()->startOfWeek()->addDays(5)); // Saturday

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
}
