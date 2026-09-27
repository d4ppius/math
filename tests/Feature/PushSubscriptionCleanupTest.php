<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * push_subscriptions (from the webpush package) is a polymorphic table with
 * no cascading foreign key, unlike every other child_id/user_id table in
 * this app — left alone, a deleted child/user/family would leave its
 * subscription behind as an orphaned row forever.
 */
class PushSubscriptionCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_child_removes_its_push_subscription(): void
    {
        $child = Child::factory()->create();
        $child->updatePushSubscription('https://push.example.com/child-a', 'a', 'a');

        $child->delete();

        $this->assertSame(0, DB::table('push_subscriptions')->count());
    }

    public function test_deleting_a_parent_removes_its_push_subscription(): void
    {
        $user = User::factory()->for(Family::factory())->create();
        $user->updatePushSubscription('https://push.example.com/parent-a', 'a', 'a');

        $user->delete();

        $this->assertSame(0, DB::table('push_subscriptions')->count());
    }

    public function test_deleting_a_family_removes_every_subscription_of_its_children_and_parents(): void
    {
        // Family::delete() removes its children/users purely via the
        // database's own cascading foreign key, which never runs their
        // individual model events — this is the one path Child/User's own
        // cleanup (see above) can't reach by itself.
        $family = Family::factory()->create();
        $user = User::factory()->for($family)->create();
        $user->updatePushSubscription('https://push.example.com/parent-a', 'a', 'a');
        $child = Child::factory()->for($family)->create();
        $child->updatePushSubscription('https://push.example.com/child-a', 'a', 'a');

        $family->delete();

        $this->assertSame(0, DB::table('push_subscriptions')->count());
    }

    public function test_the_last_parent_deleting_their_account_leaves_no_orphaned_subscription(): void
    {
        $family = Family::factory()->create();
        $user = User::factory()->for($family)->create();
        $user->updatePushSubscription('https://push.example.com/parent-a', 'a', 'a');
        $child = Child::factory()->for($family)->create();
        $child->updatePushSubscription('https://push.example.com/child-a', 'a', 'a');

        $this->actingAs($user)->delete(route('profile.destroy'), [
            'password' => 'password',
        ])->assertRedirect('/');

        $this->assertSame(0, Family::count());
        $this->assertSame(0, DB::table('push_subscriptions')->count());
    }
}
