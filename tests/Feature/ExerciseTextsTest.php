<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\Family;
use App\Models\PracticeSession;
use App\Models\User;
use App\Notifications\DailyGoalAchieved;
use App\Notifications\PracticeReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExerciseTextsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_reminder_does_not_name_a_single_exercise(): void
    {
        $child = Child::factory()->create();

        $message = (new PracticeReminder)->toWebPush($child, new PracticeReminder)->toArray();

        $this->assertStringNotContainsString('Einmaleins', json_encode($message, JSON_UNESCAPED_UNICODE));
        $this->assertStringContainsString('Lust auf ein paar Runden?', json_encode($message, JSON_UNESCAPED_UNICODE));
    }

    public function test_the_goal_mail_does_not_name_a_single_exercise(): void
    {
        $family = Family::factory()->create();
        $parent = User::factory()->for($family)->create();
        $child = Child::factory()->for($family)->create(['name' => 'Mia']);
        $session = new PracticeSession(['questions_answered' => 12, 'questions_correct' => 10, 'total_points' => 120]);

        $mail = (new DailyGoalAchieved($child, $session))->toMail($parent);

        $this->assertContains('Mia hat die heutige Übung geschafft.', $mail->introLines);
        $this->assertStringNotContainsString('Einmaleins', implode(' ', $mail->introLines));
    }

    public function test_the_manifest_and_the_public_pages_mention_both_exercises(): void
    {
        $this->assertStringContainsString('Einmaleins und Plus bis 20', file_get_contents(public_path('manifest.webmanifest')));

        $this->get(route('home'))->assertSee('Einmaleins und Plus bis 20')->assertSee('Rechnen üben, das Kindern', false);
        $this->get(route('legal.imprint'))->assertSee('Einmaleins und Plus üben');
    }
}
