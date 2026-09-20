<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\ChildExerciseSetting;
use App\Models\ChildFactStat;
use App\Models\ExerciseType;
use App\Models\PracticeSession;
use App\Models\SessionAttempt;
use Database\Seeders\ExerciseTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChildExerciseChoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ExerciseTypeSeeder::class);
    }

    private function typeId(string $key): int
    {
        return ExerciseType::where('key', $key)->value('id');
    }

    /** A child with Einmaleins on and Plus as given. */
    private function child(?bool $plusEnabled = true, array $plusGroups = [1]): Child
    {
        $child = Child::factory()->create();

        ChildExerciseSetting::create([
            'child_id' => $child->id, 'exercise_type_id' => $this->typeId('multiplication'), 'enabled' => true,
            'active_groups' => [1, 2], 'session_duration_minutes' => 10, 'target_frequency' => 'daily',
        ]);

        if ($plusEnabled !== null) {
            ChildExerciseSetting::create([
                'child_id' => $child->id, 'exercise_type_id' => $this->typeId('addition'), 'enabled' => $plusEnabled,
                'active_groups' => $plusGroups, 'session_duration_minutes' => 5, 'target_frequency' => 'daily',
            ]);
        }

        return $child;
    }

    private function start(Child $child, ?string $exercise = null)
    {
        return $this->actingAs($child, 'child')->post(route('child.sessions.start'), $exercise ? ['exercise' => $exercise] : []);
    }

    public function test_with_only_einmaleins_the_home_keeps_the_single_big_button(): void
    {
        $child = $this->child(plusEnabled: false);

        $this->actingAs($child, 'child')->get(route('child.home'))
            ->assertOk()
            ->assertSee("Los geht's!")
            ->assertSee('name="exercise" value="multiplication"', false)
            ->assertDontSee('Was möchtest du üben?')
            ->assertDontSee('Plus bis 20');
    }

    public function test_with_several_exercises_the_child_gets_a_card_for_each(): void
    {
        $child = $this->child(plusEnabled: true);

        $this->actingAs($child, 'child')->get(route('child.home'))
            ->assertOk()
            ->assertSee('Was möchtest du üben?')
            ->assertSee('Einmaleins')
            ->assertSee('Plus bis 20')
            ->assertSee('name="exercise" value="multiplication"', false)
            ->assertSee('name="exercise" value="addition"', false)
            ->assertDontSee("Los geht's!");
    }

    public function test_an_exercise_that_is_switched_off_or_missing_is_not_offered(): void
    {
        $this->actingAs($this->child(plusEnabled: false), 'child')->get(route('child.home'))->assertDontSee('value="addition"', false);
        $this->actingAs($this->child(plusEnabled: null), 'child')->get(route('child.home'))->assertDontSee('value="addition"', false);
    }

    public function test_an_exercise_an_admin_deactivated_disappears_from_the_choice(): void
    {
        $child = $this->child();
        ExerciseType::where('key', 'addition')->update(['is_active' => false]);

        $this->actingAs($child, 'child')->get(route('child.home'))
            ->assertDontSee('Plus bis 20')
            ->assertSee("Los geht's!");

        $this->start($child, 'addition')->assertRedirect(route('child.home'))->assertSessionHasErrors('exercise');
        $this->assertSame(0, PracticeSession::count());
    }

    public function test_starting_plus_creates_a_session_of_that_exercise_with_its_own_duration(): void
    {
        $child = $this->child();

        $this->start($child, 'addition')->assertRedirect();

        $session = PracticeSession::firstOrFail();
        $this->assertSame($this->typeId('addition'), $session->exercise_type_id);
        $this->assertSame(5 * 60, $session->planned_duration_seconds);
        $this->assertSame('active', $session->status);
    }

    public function test_the_server_refuses_an_exercise_that_is_not_switched_on_even_if_the_request_asks_for_it(): void
    {
        $child = $this->child(plusEnabled: false);

        $this->start($child, 'addition')->assertRedirect(route('child.home'))->assertSessionHasErrors('exercise');
        $this->start($child, 'no-such-exercise')->assertSessionHasErrors('exercise');

        $this->assertSame(0, PracticeSession::count());
    }

    public function test_without_a_choice_and_with_several_exercises_the_child_is_sent_back_to_choose(): void
    {
        $child = $this->child();

        $this->start($child)->assertRedirect(route('child.home'))->assertSessionHasErrors('exercise');

        $this->assertSame(0, PracticeSession::count());
    }

    public function test_a_request_without_a_choice_still_works_when_only_one_exercise_is_on(): void
    {
        // e.g. a page that was opened before the update.
        $this->start($this->child(plusEnabled: false))->assertRedirect();

        $this->assertSame($this->typeId('multiplication'), PracticeSession::firstOrFail()->exercise_type_id);
    }

    public function test_each_exercise_resumes_its_own_running_session(): void
    {
        $child = $this->child();

        $this->start($child, 'multiplication');
        $this->start($child, 'addition');

        $this->assertSame(2, PracticeSession::where('status', 'active')->count());

        $multiplication = PracticeSession::where('exercise_type_id', $this->typeId('multiplication'))->firstOrFail();

        $this->start($child, 'multiplication')->assertRedirect(route('child.sessions.show', $multiplication));
        $this->assertSame(2, PracticeSession::count(), 'resuming does not create another session');
    }

    public function test_the_card_offers_to_continue_a_running_session(): void
    {
        $child = $this->child();
        $this->start($child, 'addition');

        $this->actingAs($child, 'child')->get(route('child.home'))->assertSee('Weiter üben');
    }

    public function test_a_plus_session_asks_sums_from_the_switched_on_groups_and_scores_the_answers(): void
    {
        $child = $this->child(plusGroups: [1]); // sums up to 10
        $this->start($child, 'addition');
        $session = PracticeSession::firstOrFail();

        $response = $this->actingAs($child, 'child')->getJson(route('child.sessions.next-question', $session))->assertOk();

        $this->assertMatchesRegularExpression('/^\d+ \+ \d+$/', $response->json('prompt'));
        [$a, $b] = array_map('intval', explode(' + ', $response->json('prompt')));
        $this->assertLessThanOrEqual(10, $a + $b, 'only "Plus bis 10" is switched on');

        $answer = $this->actingAs($child, 'child')->postJson(route('child.sessions.attempts', $session), ['answer' => $a + $b]);

        $answer->assertOk()->assertJson(['is_correct' => true]);
        $this->assertGreaterThan(0, $answer->json('points_awarded'));
        $this->assertSame(1, SessionAttempt::count());
        $this->assertSame(1, ChildFactStat::where('child_id', $child->id)->count());
    }

    public function test_the_summary_names_the_exercise(): void
    {
        $child = $this->child();
        $this->start($child, 'addition');
        $session = PracticeSession::firstOrFail();

        $this->actingAs($child, 'child')->get(route('child.sessions.summary', $session))->assertOk()->assertSee('Plus bis 20');
    }
}
