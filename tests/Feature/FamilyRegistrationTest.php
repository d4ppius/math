<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_without_invite_creates_a_new_family(): void
    {
        $this->post('/eltern/register', [
            'family_name' => 'Familie Muster',
            'name' => 'Maria Muster',
            'email' => 'maria@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();

        $user = User::where('email', 'maria@example.com')->first();
        $this->assertNotNull($user->family);
        $this->assertSame('Familie Muster', $user->family->name);
    }

    public function test_registering_with_a_valid_invite_joins_the_existing_family(): void
    {
        $family = Family::factory()->create(['name' => 'Familie Bestand']);

        $this->post('/eltern/register?invite='.$family->invite_token, [
            'invite_token' => $family->invite_token,
            'name' => 'Papa Muster',
            'email' => 'papa@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();

        $user = User::where('email', 'papa@example.com')->first();
        $this->assertSame($family->id, $user->family_id);
        $this->assertSame(1, $family->users()->count());
    }
}
