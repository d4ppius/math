<?php

namespace Tests\Feature\Auth;

use App\Models\Family;
use App\Models\User;
use App\Services\SpamGuard;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\PassesSpamGuard;
use Tests\TestCase;

class RegistrationProtectionTest extends TestCase
{
    use PassesSpamGuard;
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'family_name' => 'Familie Muster',
            'name' => 'Maria Muster',
            'email' => 'maria@example.com',
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
        ] + $this->humanFormFields();
    }

    public function test_the_form_carries_the_invisible_protection_and_a_privacy_consent(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('name="'.SpamGuard::HONEYPOT_FIELD.'"', false)
            ->assertSee('name="'.SpamGuard::TOKEN_FIELD.'"', false)
            ->assertSee('name="privacy"', false)
            ->assertSee(route('legal.privacy'), false)
            ->assertSee('E-Mail, um deine Adresse zu bestätigen');
    }

    public function test_registering_sends_a_verification_mail_and_locks_the_parent_area_until_verified(): void
    {
        Notification::fake();

        $this->post(route('register'), $this->payload())->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'maria@example.com')->firstOrFail();
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class);

        // The parent area asks for the verification first.
        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('verification.notice'));
    }

    public function test_following_the_mailed_link_unlocks_the_parent_area(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $user->id, 'hash' => sha1($user->email)]);

        $this->actingAs($user)->get($url)->assertRedirect(route('dashboard', absolute: false).'?verified=1');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->actingAs($user->fresh())->get(route('dashboard'))->assertOk();
    }

    public function test_the_verification_page_and_mails_are_in_german(): void
    {
        $user = User::factory()->unverified()->create(['name' => 'Maria']);

        $this->actingAs($user)->get(route('verification.notice'))->assertSee('Danke für deine Registrierung')->assertDontSee('Thanks for signing up');

        $verify = (new VerifyEmail)->toMail($user);
        $this->assertSame('Bitte bestätige deine E-Mail-Adresse', $verify->subject);
        $this->assertSame('E-Mail-Adresse bestätigen', $verify->actionText);
        $this->assertStringContainsString('Maria', $verify->greeting);
        $this->assertStringContainsString('verify-email/', $verify->actionUrl);

        $reset = (new ResetPassword('abc123'))->toMail($user);
        $this->assertSame('Passwort zurücksetzen', $reset->subject);
        $this->assertSame('Neues Passwort festlegen', $reset->actionText);
        $this->assertStringContainsString('reset-password/abc123', $reset->actionUrl);
        $this->assertStringContainsString(urlencode($user->email), $reset->actionUrl);
    }

    public function test_the_privacy_consent_is_required(): void
    {
        $this->post(route('register'), $this->payload(['privacy' => null]))->assertSessionHasErrors('privacy');

        $this->assertSame(0, User::count());
    }

    public function test_a_filled_honeypot_creates_nothing_but_looks_like_it_worked(): void
    {
        $this->post(route('register'), $this->payload([SpamGuard::HONEYPOT_FIELD => 'http://spam.example']))->assertRedirect(route('login'));

        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function test_an_instant_or_forged_submission_is_rejected(): void
    {
        $tooFast = $this->payload();
        $tooFast[SpamGuard::TOKEN_FIELD] = app(SpamGuard::class)->token();
        $this->post(route('register'), $tooFast)->assertSessionHasErrors('form');

        foreach ([null, 'forged'] as $token) {
            $this->post(route('register'), $this->payload([SpamGuard::TOKEN_FIELD => $token]))->assertSessionHasErrors('form');
        }

        $this->assertSame(0, User::count());
    }

    public function test_one_address_can_register_at_most_ten_accounts_per_hour(): void
    {
        foreach (range(1, 10) as $i) {
            $this->post(route('register'), $this->payload(['email' => "eltern{$i}@example.com"]))->assertSessionHasNoErrors();
            auth()->logout();
        }

        $this->post(route('register'), $this->payload(['email' => 'eltern11@example.com']))->assertSessionHasErrors('form');

        $this->assertSame(10, User::count());
    }

    public function test_joining_by_invitation_is_protected_and_verified_in_the_same_way(): void
    {
        Notification::fake();
        $family = Family::factory()->create();

        $this->post(route('register'), $this->payload(['invite_token' => $family->invite_token, 'family_name' => null]))->assertRedirect();

        $user = User::where('email', 'maria@example.com')->firstOrFail();
        $this->assertSame($family->id, $user->family_id);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    protected function tearDown(): void
    {
        RateLimiter::clear('register');
        parent::tearDown();
    }
}
