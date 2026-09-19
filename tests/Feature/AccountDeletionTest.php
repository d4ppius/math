<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\ChildFactStat;
use App\Models\ExerciseType;
use App\Models\Fact;
use App\Models\Family;
use App\Models\PracticeSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_last_parent_deleting_their_account_removes_the_family_and_all_child_data(): void
    {
        $family = Family::factory()->create();
        $user = User::factory()->for($family)->create();
        $child = Child::factory()->for($family)->create();

        $type = ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins']);
        $fact = Fact::create(['exercise_type_id' => $type->id, 'operand_a' => 2, 'operand_b' => 3, 'correct_answer' => 6, 'difficulty_group' => 2]);
        ChildFactStat::create(['child_id' => $child->id, 'fact_id' => $fact->id, 'attempts_total' => 1, 'attempts_correct' => 1]);
        PracticeSession::create([
            'child_id' => $child->id, 'exercise_type_id' => $type->id, 'started_at' => now(),
            'planned_duration_seconds' => 600, 'status' => 'completed',
        ]);

        $this->actingAs($user)
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertRedirect('/');

        $this->assertModelMissing($user);
        $this->assertModelMissing($family);
        $this->assertModelMissing($child);
        $this->assertSame(0, ChildFactStat::count());
        $this->assertSame(0, PracticeSession::count());
        // Shared catalogue data is not touched.
        $this->assertModelExists($fact);
    }

    public function test_the_family_and_children_stay_when_another_parent_remains(): void
    {
        $family = Family::factory()->create();
        $leaving = User::factory()->for($family)->create();
        $staying = User::factory()->for($family)->create();
        $child = Child::factory()->for($family)->create();

        $this->actingAs($leaving)->delete(route('profile.destroy'), ['password' => 'password'])->assertRedirect('/');

        $this->assertModelMissing($leaving);
        $this->assertModelExists($staying);
        $this->assertModelExists($family);
        $this->assertModelExists($child);
    }

    public function test_a_wrong_password_deletes_nothing(): void
    {
        $family = Family::factory()->create();
        $user = User::factory()->for($family)->create();

        $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'wrong'])->assertSessionHasErrorsIn('userDeletion', 'password');

        $this->assertModelExists($user);
        $this->assertModelExists($family);
    }
}
