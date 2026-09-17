<?php

namespace Tests\Feature;

use App\Models\Child;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChildPushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_logged_in_child_can_store_a_push_subscription(): void
    {
        $child = Child::factory()->create();

        $response = $this->actingAs($child, 'child')->postJson(route('child.push-subscriptions.store'), [
            'endpoint' => 'https://push.example.com/child-abc',
            'keys' => ['p256dh' => 'fake-p256dh', 'auth' => 'fake-auth'],
        ]);

        $response->assertOk();
        $this->assertSame(1, $child->pushSubscriptions()->count());
    }

    public function test_a_logged_in_child_can_remove_a_push_subscription(): void
    {
        $child = Child::factory()->create();
        $child->updatePushSubscription('https://push.example.com/child-abc', 'fake-p256dh', 'fake-auth');

        $response = $this->actingAs($child, 'child')->deleteJson(route('child.push-subscriptions.destroy'), [
            'endpoint' => 'https://push.example.com/child-abc',
        ]);

        $response->assertOk();
        $this->assertSame(0, $child->pushSubscriptions()->count());
    }

    public function test_a_guest_child_cannot_store_a_push_subscription(): void
    {
        $this->postJson(route('child.push-subscriptions.store'), [
            'endpoint' => 'https://push.example.com/child-abc',
            'keys' => ['p256dh' => 'fake-p256dh', 'auth' => 'fake-auth'],
        ])->assertUnauthorized();
    }
}
