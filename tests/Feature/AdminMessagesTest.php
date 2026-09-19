<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMessagesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->save();

        return $admin;
    }

    private function message(array $attributes = []): ContactMessage
    {
        return ContactMessage::create($attributes + [
            'name' => 'Erika Muster', 'email' => 'erika@example.com', 'topic' => 'support', 'message' => "Erste Zeile\nZweite Zeile",
        ]);
    }

    public function test_only_admins_can_see_the_messages(): void
    {
        $message = $this->message();

        $this->get(route('admin.messages.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('admin.messages.index'))->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('admin.messages.show', $message))->assertNotFound();
        $this->actingAs(User::factory()->create())->patch(route('admin.messages.update', $message), ['handled' => 1])->assertNotFound();
        $this->actingAs(User::factory()->create())->delete(route('admin.messages.destroy', $message))->assertNotFound();
    }

    public function test_open_messages_are_listed_by_default_with_a_filter_for_handled_ones(): void
    {
        $open = $this->message(['name' => 'Offene Person']);
        $done = $this->message(['name' => 'Erledigte Person', 'handled_at' => now()]);

        $admin = $this->actingAs($this->admin());

        $admin->get(route('admin.messages.index'))->assertOk()->assertSee('Offene Person')->assertDontSee('Erledigte Person');
        $admin->get(route('admin.messages.index', ['show' => 'handled']))->assertSee('Erledigte Person')->assertDontSee('Offene Person');
        $admin->get(route('admin.messages.index', ['show' => 'all']))->assertSee('Offene Person')->assertSee('Erledigte Person');
    }

    public function test_the_admin_can_read_reply_mark_handled_reopen_and_delete(): void
    {
        $message = $this->message();
        $admin = $this->actingAs($this->admin());

        $admin->get(route('admin.messages.show', $message))
            ->assertOk()
            ->assertSee('Erika Muster')
            ->assertSee('Hilfe bei einem Problem')
            ->assertSee('mailto:erika@example.com', false);

        $admin->patch(route('admin.messages.update', $message), ['handled' => 1])->assertRedirect(route('admin.messages.show', $message));
        $this->assertTrue($message->refresh()->isHandled());

        $admin->patch(route('admin.messages.update', $message), ['handled' => 0]);
        $this->assertFalse($message->refresh()->isHandled());

        $admin->delete(route('admin.messages.destroy', $message))->assertRedirect(route('admin.messages.index'));
        $this->assertModelMissing($message);
    }

    public function test_the_open_count_shows_in_the_navigation_and_on_the_dashboard(): void
    {
        $this->message();
        $this->message();
        $this->message(['handled_at' => now()]);

        $admin = $this->actingAs($this->admin());

        $admin->get(route('admin.dashboard'))->assertSee('2 offene Nachrichten aus dem Kontaktformular')->assertSee('Nachrichten');
    }

    public function test_the_dashboard_has_no_message_hint_when_everything_is_handled(): void
    {
        $this->message(['handled_at' => now()]);

        $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertDontSee('offene Nachricht');
    }
}
