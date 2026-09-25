<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\ChildExerciseSetting;
use App\Models\ExerciseType;
use App\Models\Fact;
use App\Models\Family;
use App\Models\PracticeSession;
use App\Models\SessionAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->save();

        return $admin;
    }

    public function test_guests_are_sent_to_login_and_regular_parents_get_a_404(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));

        $parent = User::factory()->create();

        foreach (['admin.dashboard', 'admin.families.index', 'admin.children.index', 'admin.sessions.index', 'admin.exercises.index'] as $route) {
            $this->actingAs($parent)->get(route($route))->assertNotFound();
        }
    }

    public function test_the_admin_link_is_only_shown_to_admins(): void
    {
        $this->actingAs(User::factory()->create())->get(route('dashboard'))
            ->assertOk()->assertDontSee(route('admin.dashboard'));

        $this->actingAs($this->admin())->get(route('dashboard'))
            ->assertOk()->assertSee(route('admin.dashboard'));
    }

    public function test_admin_can_browse_all_overview_pages(): void
    {
        $exerciseType = ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins']);
        $fact = Fact::create(['exercise_type_id' => $exerciseType->id, 'operand_a' => 3, 'operand_b' => 4, 'correct_answer' => 12, 'difficulty_group' => 3]);

        $family = Family::factory()->create(['name' => 'Familie Muster']);
        $parent = User::factory()->for($family)->create(['email' => 'mama@example.com']);
        $child = Child::factory()->for($family)->create(['name' => 'Lena']);

        $session = PracticeSession::create([
            'child_id' => $child->id, 'exercise_type_id' => $exerciseType->id, 'started_at' => now(),
            'planned_duration_seconds' => 600, 'status' => 'completed', 'total_points' => 7,
        ]);
        SessionAttempt::create([
            'practice_session_id' => $session->id, 'fact_id' => $fact->id, 'given_answer' => 12, 'is_correct' => true,
            'response_time_ms' => 2500, 'points_awarded' => 7, 'question_issued_at' => now(), 'answered_at' => now(),
        ]);

        $admin = $this->actingAs($this->admin());

        $admin->get(route('admin.dashboard'))->assertOk()->assertSee('Lena');
        $admin->get(route('admin.families.index'))->assertOk()->assertSee('Familie Muster');
        $admin->get(route('admin.families.index', ['q' => 'gibtsnicht']))->assertOk()->assertDontSee('Familie Muster');
        $admin->get(route('admin.families.show', $family))->assertOk()->assertSee('mama@example.com')->assertSee('Lena');
        $admin->get(route('admin.children.index'))->assertOk()->assertSee('Lena')->assertSee('Familie Muster');
        $admin->get(route('admin.sessions.index'))->assertOk()->assertSee('Lena');
        $admin->get(route('admin.sessions.show', $session))->assertOk()->assertSee('3 × 4');
        $admin->get(route('admin.exercises.index'))->assertOk()->assertSee('Einmaleins');
        $admin->get(route('admin.exercises.facts.index', $exerciseType))->assertOk()->assertSee('12');
        $admin->get(route('admin.facts.edit', $fact))->assertOk();
    }

    public function test_the_dashboard_counts_exclude_preview_sessions_which_stay_visible_and_labelled_in_the_lists(): void
    {
        $exerciseType = ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins']);
        $child = Child::factory()->create();

        $session = PracticeSession::create([
            'child_id' => $child->id, 'exercise_type_id' => $exerciseType->id, 'started_at' => now(),
            'planned_duration_seconds' => 600, 'status' => 'completed', 'is_preview' => true,
        ]);

        $admin = $this->actingAs($this->admin());

        $admin->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('counts', fn ($counts) => $counts['sessions'] === 0 && $counts['practicedToday'] === 0)
            ->assertSee('Vorschau');

        $admin->get(route('admin.sessions.index'))->assertOk()->assertSee('Vorschau');
        $admin->get(route('admin.sessions.show', $session))->assertOk()->assertSee('Vorschau');
    }

    public function test_admin_can_edit_and_delete_families_but_not_their_own(): void
    {
        $admin = $this->admin();
        $other = Family::factory()->create();
        $otherParent = User::factory()->for($other)->create();
        $otherChild = Child::factory()->for($other)->create();

        $this->actingAs($admin)
            ->put(route('admin.families.update', $other), ['name' => 'Neuer Name', 'timezone' => 'Europe/Berlin'])
            ->assertRedirect(route('admin.families.show', $other));
        $this->assertSame('Neuer Name', $other->refresh()->name);

        $this->actingAs($admin)
            ->put(route('admin.families.update', $other), ['name' => 'X', 'timezone' => 'Mars/Olympus'])
            ->assertSessionHasErrors('timezone');

        $this->actingAs($admin)->delete(route('admin.families.destroy', $admin->family))
            ->assertSessionHas('error');
        $this->assertModelExists($admin->family);

        $this->actingAs($admin)->delete(route('admin.families.destroy', $other))
            ->assertRedirect(route('admin.families.index'));
        $this->assertModelMissing($other);
        $this->assertModelMissing($otherParent);
        $this->assertModelMissing($otherChild);
    }

    public function test_admin_can_delete_a_parent_but_not_themselves_or_another_admin(): void
    {
        $admin = $this->admin();
        $parent = User::factory()->create();
        $otherAdmin = $this->admin();

        $this->actingAs($admin)->delete(route('admin.users.destroy', $parent))->assertRedirect();
        $this->assertModelMissing($parent);

        $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))->assertSessionHas('error');
        $this->actingAs($admin)->delete(route('admin.users.destroy', $otherAdmin))->assertSessionHas('error');
        $this->assertModelExists($admin);
        $this->assertModelExists($otherAdmin);
    }

    public function test_admin_can_manage_children_of_any_family_through_the_parent_screens(): void
    {
        ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins']);
        $child = Child::factory()->for(Family::factory()->create())->create();

        $regularParent = User::factory()->create();
        $this->actingAs($regularParent)->get(route('parent.children.edit', $child))->assertForbidden();

        $admin = $this->actingAs($this->admin());
        $admin->get(route('parent.children.edit', $child))->assertOk();
        $admin->get(route('parent.children.exercise-settings.edit', $child))->assertOk();
        $admin->get(route('parent.children.statistics', $child))->assertOk();
        $admin->put(route('parent.children.update', $child), [
            'name' => 'Umbenannt', 'avatar' => 'fox', 'color_theme' => 'blue', 'active' => 0,
        ])->assertRedirect();

        $this->assertSame('Umbenannt', $child->refresh()->name);
        $this->assertFalse($child->active);
    }

    public function test_admin_can_impersonate_a_parent_and_return(): void
    {
        $admin = $this->admin();
        $parent = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.impersonate', $parent))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($parent);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Zurück zum Admin')
            // The impersonated parent must not gain access to the admin area.
            ->assertDontSee(route('admin.dashboard'));
        $this->get(route('admin.dashboard'))->assertNotFound();

        $this->post(route('impersonation.stop'))->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_impersonation_cannot_be_abused(): void
    {
        $parent = User::factory()->create();

        // A regular parent can neither start impersonation nor use the way back.
        $this->actingAs($parent)->post(route('admin.users.impersonate', User::factory()->create()))->assertNotFound();
        $this->actingAs($parent)->post(route('impersonation.stop'))->assertForbidden();

        // A forged session value pointing at a non-admin is rejected too.
        $this->actingAs($parent)->withSession(['impersonator_id' => User::factory()->create()->id])
            ->post(route('impersonation.stop'))->assertForbidden();

        // Admins can't be impersonated.
        $this->actingAs($this->admin())->post(route('admin.users.impersonate', $this->admin()))->assertSessionHas('error');
    }

    public function test_admin_can_deactivate_an_exercise_and_children_can_no_longer_start_it(): void
    {
        $exerciseType = ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins']);
        $child = Child::factory()->create();
        ChildExerciseSetting::create([
            'child_id' => $child->id, 'exercise_type_id' => $exerciseType->id,
            'active_groups' => [1], 'session_duration_minutes' => 10, 'target_frequency' => 'daily',
        ]);

        $this->actingAs($this->admin())
            ->patch(route('admin.exercises.update', $exerciseType), ['is_active' => 0])
            ->assertRedirect(route('admin.exercises.index'));
        $this->assertFalse($exerciseType->refresh()->is_active);

        $this->actingAs($child, 'child')->post(route('child.sessions.start'))
            ->assertRedirect(route('child.home'))
            ->assertSessionHasErrors('exercise');
        $this->assertSame(0, PracticeSession::count());

        $this->actingAs($this->admin())->patch(route('admin.exercises.update', $exerciseType), ['is_active' => 1]);
        $this->actingAs($child, 'child')->post(route('child.sessions.start'));
        $this->assertSame(1, PracticeSession::count());
    }

    public function test_admin_can_edit_a_fact_but_not_create_a_duplicate(): void
    {
        $exerciseType = ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins']);
        $fact = Fact::create(['exercise_type_id' => $exerciseType->id, 'operand_a' => 3, 'operand_b' => 4, 'correct_answer' => 12, 'difficulty_group' => 3]);
        Fact::create(['exercise_type_id' => $exerciseType->id, 'operand_a' => 5, 'operand_b' => 4, 'correct_answer' => 20, 'difficulty_group' => 5]);

        $admin = $this->actingAs($this->admin());

        $admin->put(route('admin.facts.update', $fact), [
            'operand_a' => 3, 'operand_b' => 4, 'correct_answer' => 12, 'difficulty_group' => 4,
        ])->assertRedirect(route('admin.exercises.facts.index', $exerciseType));
        $this->assertSame(4, $fact->refresh()->difficulty_group);

        $admin->put(route('admin.facts.update', $fact), [
            'operand_a' => 5, 'operand_b' => 4, 'correct_answer' => 20, 'difficulty_group' => 5,
        ])->assertSessionHasErrors('operand_a');
    }

    public function test_the_make_admin_command_grants_and_revokes_admin_rights(): void
    {
        $user = User::factory()->create(['email' => 'boss@example.com']);
        $this->assertFalse($user->refresh()->is_admin);

        $this->artisan('app:make-admin', ['email' => 'BOSS@example.com'])->assertSuccessful();
        $this->assertTrue($user->refresh()->is_admin);

        $this->artisan('app:make-admin', ['email' => 'boss@example.com', '--revoke' => true])->assertSuccessful();
        $this->assertFalse($user->refresh()->is_admin);

        $this->artisan('app:make-admin', ['email' => 'nobody@example.com'])->assertFailed();
    }

    public function test_is_admin_cannot_be_set_through_mass_assignment(): void
    {
        $user = User::create([
            'family_id' => Family::factory()->create()->id,
            'name' => 'Sneaky', 'email' => 'sneaky@example.com', 'password' => 'secret-password',
            'is_admin' => true,
        ]);

        $this->assertFalse($user->refresh()->is_admin);
    }
}
