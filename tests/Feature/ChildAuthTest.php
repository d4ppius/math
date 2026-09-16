<?php

namespace Tests\Feature;

use App\Models\Child;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChildAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_visiting_the_magic_link_logs_the_child_in_directly_without_a_pin(): void
    {
        $child = Child::factory()->create(['name' => 'Lea']);
        $plainToken = $child->generateLoginToken();

        $response = $this->get('/k/'.$plainToken);

        // No redirect: the magic-link URL itself must render the destination,
        // otherwise "Add to Home Screen" would bookmark a token-less URL.
        $response->assertOk()->assertSee('Lea');
        $this->assertAuthenticated('child');
        $this->assertSame($child->id, auth('child')->id());
    }

    public function test_an_invalid_token_returns_404(): void
    {
        $this->get('/k/does-not-exist')->assertNotFound();
    }

    public function test_a_child_with_a_pin_must_enter_it_before_being_logged_in(): void
    {
        $child = Child::factory()->create();
        $plainToken = $child->generateLoginToken();
        $child->setPin('1234');

        $response = $this->get('/k/'.$plainToken);
        $response->assertOk()->assertSee('Gib deinen Code ein');
        $this->assertGuest('child');

        $this->post('/k/'.$plainToken, ['pin' => '0000'])
            ->assertOk()
            ->assertSee('Falscher Code');
        $this->assertGuest('child');

        $this->post('/k/'.$plainToken, ['pin' => '1234'])
            ->assertOk()
            ->assertSee($child->name);
        $this->assertAuthenticated('child');
    }
}
