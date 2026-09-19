<?php

namespace Tests\Feature\Public;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\User;
use App\Services\SpamGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['contact.recipient' => 'support@rechenfuchs.example']);
        Mail::fake();
    }

    /** A submission as a human would send it: the form was rendered a while ago. */
    private function humanPayload(array $overrides = []): array
    {
        $token = app(SpamGuard::class)->token();
        $this->travel(15)->seconds();

        return array_merge([
            'name' => 'Erika Muster',
            'email' => 'erika@example.com',
            'topic' => 'support',
            'message' => 'Hallo, ich habe eine Frage zur Einrichtung des Homescreen-Icons.',
            'privacy' => '1',
            SpamGuard::HONEYPOT_FIELD => '',
            SpamGuard::TOKEN_FIELD => $token,
        ], $overrides);
    }

    public function test_the_form_page_renders_with_the_invisible_protection_fields(): void
    {
        $this->get(route('contact.show'))
            ->assertOk()
            ->assertSee('Kontakt', false)
            ->assertSee('name="'.SpamGuard::HONEYPOT_FIELD.'"', false)
            ->assertSee('name="'.SpamGuard::TOKEN_FIELD.'"', false)
            ->assertSee('Datenschutzerklärung');
    }

    public function test_a_valid_message_is_stored_and_mailed_with_the_visitor_as_reply_to(): void
    {
        $this->post(route('contact.store'), $this->humanPayload())
            ->assertRedirect(route('contact.show'))
            ->assertSessionHas('sent');

        $stored = ContactMessage::firstOrFail();
        $this->assertSame('Erika Muster', $stored->name);
        $this->assertSame('support', $stored->topic);
        $this->assertNull($stored->handled_at);

        Mail::assertSent(ContactMessageReceived::class, function (ContactMessageReceived $mail) {
            return $mail->hasTo('support@rechenfuchs.example')
                && $mail->hasReplyTo('erika@example.com')
                && str_contains($mail->envelope()->subject, 'Hilfe bei einem Problem');
        });

        $this->followingRedirects()->get(route('contact.show'))->assertSee('Danke für deine Nachricht!');
    }

    public function test_the_mail_body_is_plain_text_and_keeps_special_characters_unescaped(): void
    {
        $stored = ContactMessage::create(['name' => 'Ben <b>', 'email' => 'ben@example.com', 'topic' => 'other', 'message' => "Tom & Jerry's \"Test\"\nZweite Zeile"]);

        $mail = (new ContactMessageReceived($stored))->render();

        $this->assertStringContainsString("Tom & Jerry's \"Test\"", $mail);
        $this->assertStringNotContainsString('&amp;', $mail);
    }

    public function test_line_breaks_in_the_name_cannot_inject_mail_headers_through_the_subject(): void
    {
        $stored = ContactMessage::create(['name' => "Evil\r\nBcc: victim@example.com", 'email' => 'e@example.com', 'topic' => 'other', 'message' => 'Nachricht mit genug Zeichen']);

        $subject = (new ContactMessageReceived($stored))->envelope()->subject;

        $this->assertStringNotContainsString("\n", $subject);
        $this->assertStringNotContainsString("\r", $subject);
    }

    public function test_a_filled_honeypot_looks_successful_to_the_bot_but_stores_and_sends_nothing(): void
    {
        $this->post(route('contact.store'), $this->humanPayload([SpamGuard::HONEYPOT_FIELD => 'http://spam.example']))
            ->assertRedirect(route('contact.show'))
            ->assertSessionHas('sent');

        $this->assertSame(0, ContactMessage::count());
        Mail::assertNothingSent();
    }

    public function test_a_submission_that_arrives_too_quickly_is_rejected_but_keeps_the_text(): void
    {
        $payload = $this->humanPayload();
        $payload[SpamGuard::TOKEN_FIELD] = app(SpamGuard::class)->token(); // issued just now

        $this->from(route('contact.show'))
            ->post(route('contact.store'), $payload)
            ->assertRedirect(route('contact.show'))
            ->assertSessionHasErrors('form')
            ->assertSessionHasInput('message', $payload['message']);

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_a_missing_forged_or_expired_token_is_rejected(): void
    {
        foreach ([null, 'not-a-real-token'] as $token) {
            $this->post(route('contact.store'), $this->humanPayload([SpamGuard::TOKEN_FIELD => $token]))
                ->assertSessionHasErrors('form');
        }

        $old = app(SpamGuard::class)->token();
        $this->travel(2)->days();
        $this->post(route('contact.store'), $this->humanPayload([SpamGuard::TOKEN_FIELD => $old]) + [])
            ->assertSessionHasErrors('form');

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_validation_errors_are_reported_and_nothing_is_stored(): void
    {
        $cases = [
            'no name' => [['name' => ''], 'name'],
            'bad email' => [['email' => 'not-an-email'], 'email'],
            'unknown topic' => [['topic' => 'hack'], 'topic'],
            'too short' => [['message' => 'Hi'], 'message'],
            'too long' => [['message' => str_repeat('x', 3001)], 'message'],
            'no consent' => [['privacy' => null], 'privacy'],
            'link spam' => [['message' => 'Kauft hier http://a.example http://b.example www.c.example jetzt'], 'message'],
        ];

        foreach ($cases as [$override, $field]) {
            $this->post(route('contact.store'), $this->humanPayload($override))->assertSessionHasErrors($field);
        }

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_up_to_two_links_are_fine(): void
    {
        $this->post(route('contact.store'), $this->humanPayload(['message' => 'Siehe https://a.example und https://b.example bitte']))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, ContactMessage::count());
    }

    public function test_the_same_email_can_only_send_three_messages_per_hour(): void
    {
        foreach (range(1, 3) as $i) {
            $this->post(route('contact.store'), $this->humanPayload())->assertSessionHasNoErrors();
        }

        $this->post(route('contact.store'), $this->humanPayload())->assertSessionHasErrors('form');
        $this->assertSame(3, ContactMessage::count());

        // Another sender is unaffected.
        $this->post(route('contact.store'), $this->humanPayload(['email' => 'other@example.com']))->assertSessionHasNoErrors();
        $this->assertSame(4, ContactMessage::count());
    }

    public function test_one_address_can_only_send_ten_messages_per_hour_across_emails(): void
    {
        foreach (range(1, 10) as $i) {
            $this->post(route('contact.store'), $this->humanPayload(['email' => "user{$i}@example.com"]))->assertSessionHasNoErrors();
        }

        $this->post(route('contact.store'), $this->humanPayload(['email' => 'user11@example.com']))->assertSessionHasErrors('form');
        $this->assertSame(10, ContactMessage::count());
    }

    public function test_the_message_is_kept_even_if_the_mail_server_fails(): void
    {
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('SMTP down'));

        $this->post(route('contact.store'), $this->humanPayload())
            ->assertRedirect(route('contact.show'))
            ->assertSessionHas('sent');

        $this->assertSame(1, ContactMessage::count());
    }

    public function test_without_a_configured_recipient_the_message_is_only_stored(): void
    {
        config(['contact.recipient' => null]);

        $this->post(route('contact.store'), $this->humanPayload())->assertSessionHas('sent');

        $this->assertSame(1, ContactMessage::count());
        Mail::assertNothingSent();
    }

    public function test_the_form_is_prefilled_for_a_logged_in_parent(): void
    {
        $user = User::factory()->create(['name' => 'Papa Muster', 'email' => 'papa@example.com']);

        $this->actingAs($user)->get(route('contact.show'))
            ->assertSee('value="Papa Muster"', false)
            ->assertSee('value="papa@example.com"', false);
    }

    public function test_only_handled_messages_older_than_the_retention_period_are_pruned(): void
    {
        config(['contact.retention_months' => 12]);
        $make = fn (?string $handled) => ContactMessage::create([
            'name' => 'A', 'email' => 'a@example.com', 'topic' => 'other', 'message' => 'Eine Nachricht.', 'handled_at' => $handled,
        ]);

        $oldHandled = $make(now()->subMonths(13)->toDateTimeString());
        $recentHandled = $make(now()->subMonths(2)->toDateTimeString());
        $oldButOpen = $make(null);
        $oldButOpen->forceFill(['created_at' => now()->subMonths(20)])->save();

        $this->artisan('contact:prune')->assertSuccessful();

        $this->assertModelMissing($oldHandled);
        $this->assertModelExists($recentHandled);
        $this->assertModelExists($oldButOpen);
    }

    protected function tearDown(): void
    {
        RateLimiter::clear('contact');
        parent::tearDown();
    }
}
