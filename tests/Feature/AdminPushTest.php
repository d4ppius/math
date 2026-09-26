<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\Family;
use App\Models\User;
use App\Notifications\AdminBroadcast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminPushTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->save();

        return $admin;
    }

    public function test_guests_and_regular_parents_cannot_reach_the_push_page(): void
    {
        $this->get(route('admin.push.edit'))->assertRedirect(route('login'));

        $parent = User::factory()->create();
        $this->actingAs($parent)->get(route('admin.push.edit'))->assertNotFound();
        $this->actingAs($parent)->post(route('admin.push.send'), [
            'audience' => 'all_parents', 'title' => 'Hi', 'body' => 'Test',
        ])->assertNotFound();
    }

    public function test_sending_to_all_parents_only_reaches_subscribed_ones(): void
    {
        Notification::fake();

        $admin = $this->admin(); // a User too, so it counts towards "Eltern"
        $subscribed = User::factory()->for(Family::factory())->create();
        $subscribed->updatePushSubscription('https://push.example.com/parent-a', 'a', 'a');
        $unsubscribed = User::factory()->for(Family::factory())->create();
        $child = Child::factory()->create();
        $child->updatePushSubscription('https://push.example.com/child-a', 'a', 'a');

        $this->actingAs($admin)
            ->post(route('admin.push.send'), ['audience' => 'all_parents', 'title' => 'Wartung', 'body' => 'Heute Abend kurz offline.'])
            ->assertRedirect(route('admin.push.edit'))
            ->assertSessionHas('status', function ($status) {
                return str_contains($status, 'An 1 von 3 Eltern gesendet.');
            });

        Notification::assertSentTo($subscribed, AdminBroadcast::class);
        Notification::assertNotSentTo($unsubscribed, AdminBroadcast::class);
        Notification::assertNotSentTo($child, AdminBroadcast::class);
    }

    public function test_sending_to_all_children_only_reaches_subscribed_ones(): void
    {
        Notification::fake();

        $subscribed = Child::factory()->create();
        $subscribed->updatePushSubscription('https://push.example.com/child-a', 'a', 'a');
        $unsubscribed = Child::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.push.send'), ['audience' => 'all_children', 'title' => 'Hallo', 'body' => 'Viel Spass beim Üben!'])
            ->assertSessionHas('status', function ($status) {
                return str_contains($status, 'An 1 von 2 Kindern gesendet.');
            });

        Notification::assertSentTo($subscribed, AdminBroadcast::class);
        Notification::assertNotSentTo($unsubscribed, AdminBroadcast::class);
    }

    public function test_sending_to_a_specific_subscribed_person_reaches_only_them(): void
    {
        Notification::fake();

        $parent = User::factory()->create();
        $parent->updatePushSubscription('https://push.example.com/parent-a', 'a', 'a');
        $otherParent = User::factory()->create();
        $otherParent->updatePushSubscription('https://push.example.com/parent-b', 'b', 'b');

        $this->actingAs($this->admin())
            ->post(route('admin.push.send'), [
                'audience' => 'specific', 'recipient' => "user:{$parent->id}", 'title' => 'Hi', 'body' => 'Nur für dich.',
            ])
            ->assertSessionHas('status', function ($status) {
                return str_contains($status, 'An 1 von 1 Person gesendet.');
            });

        Notification::assertSentTo($parent, AdminBroadcast::class);
        Notification::assertNotSentTo($otherParent, AdminBroadcast::class);
    }

    public function test_sending_to_a_specific_unsubscribed_person_sends_nothing_but_still_succeeds(): void
    {
        Notification::fake();

        $child = Child::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.push.send'), [
                'audience' => 'specific', 'recipient' => "child:{$child->id}", 'title' => 'Hi', 'body' => 'Test',
            ])
            ->assertSessionHas('status', function ($status) {
                return str_contains($status, 'An 0 von 1 Person gesendet.');
            });

        Notification::assertNothingSent();
    }

    public function test_a_bell_badge_marks_subscribed_parents_and_children_in_the_existing_admin_lists(): void
    {
        $family = Family::factory()->create();
        $subscribedParent = User::factory()->for($family)->create();
        $subscribedParent->updatePushSubscription('https://push.example.com/parent-a', 'a', 'a');
        $unsubscribedParent = User::factory()->for($family)->create();

        $subscribedChild = Child::factory()->for($family)->create(['name' => 'Liv']);
        $subscribedChild->updatePushSubscription('https://push.example.com/child-a', 'a', 'a');
        $unsubscribedChild = Child::factory()->for($family)->create(['name' => 'Elly']);

        $admin = $this->actingAs($this->admin());

        // One 🔔 for the subscribed parent, one for the subscribed child — none for the other two.
        $familyPage = $admin->get(route('admin.families.show', $family))->assertOk()->getContent();
        $this->assertSame(2, substr_count($familyPage, '🔔'));

        // Only Liv is subscribed, so exactly one 🔔 shows up among the two children.
        $childrenPage = $admin->get(route('admin.children.index'))->assertOk()->getContent();
        $this->assertSame(1, substr_count($childrenPage, '🔔'));
    }

    public function test_title_and_body_are_required(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.push.send'), ['audience' => 'all_parents'])
            ->assertSessionHasErrors(['title', 'body']);
    }
}
