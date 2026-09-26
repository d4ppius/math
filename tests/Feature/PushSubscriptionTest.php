<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_parent_can_store_a_push_subscription(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('parent.push-subscriptions.store'), [
            'endpoint' => 'https://push.example.com/abc123',
            'keys' => ['p256dh' => 'fake-p256dh', 'auth' => 'fake-auth'],
        ]);

        $response->assertOk();
        $this->assertSame(1, $user->pushSubscriptions()->count());
    }

    public function test_a_parent_can_remove_a_push_subscription(): void
    {
        $user = User::factory()->create();
        $user->updatePushSubscription('https://push.example.com/abc123', 'fake-p256dh', 'fake-auth');

        $response = $this->actingAs($user)->deleteJson(route('parent.push-subscriptions.destroy'), [
            'endpoint' => 'https://push.example.com/abc123',
        ]);

        $response->assertOk();
        $this->assertSame(0, $user->pushSubscriptions()->count());
    }

    public function test_removing_without_an_endpoint_clears_every_subscription(): void
    {
        // The device clicking "Deaktivieren" might not be the one that
        // subscribed (the database can say "subscribed" because of a
        // different device) — no endpoint to report still has to mean
        // deactivated.
        $user = User::factory()->create();
        $user->updatePushSubscription('https://push.example.com/device-a', 'a', 'a');
        $user->updatePushSubscription('https://push.example.com/device-b', 'b', 'b');

        $response = $this->actingAs($user)->deleteJson(route('parent.push-subscriptions.destroy'), []);

        $response->assertOk();
        $this->assertSame(0, $user->pushSubscriptions()->count());
    }

    public function test_the_dashboard_shows_the_real_subscription_state_after_a_reload(): void
    {
        // Both button labels are always in the markup (Alpine's x-show toggles
        // them client-side), so what a reload must get right is the Alpine
        // status the page boots with, not which text merely appears on the page.
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee("status: 'idle'", false);

        $user->updatePushSubscription('https://push.example.com/abc123', 'fake-p256dh', 'fake-auth');

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee("status: 'subscribed'", false);
    }

    public function test_guests_cannot_store_a_push_subscription(): void
    {
        $this->postJson(route('parent.push-subscriptions.store'), [
            'endpoint' => 'https://push.example.com/abc123',
            'keys' => ['p256dh' => 'fake-p256dh', 'auth' => 'fake-auth'],
        ])->assertUnauthorized();
    }
}
