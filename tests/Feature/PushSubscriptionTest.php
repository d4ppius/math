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

    public function test_guests_cannot_store_a_push_subscription(): void
    {
        $this->postJson(route('parent.push-subscriptions.store'), [
            'endpoint' => 'https://push.example.com/abc123',
            'keys' => ['p256dh' => 'fake-p256dh', 'auth' => 'fake-auth'],
        ])->assertUnauthorized();
    }
}
