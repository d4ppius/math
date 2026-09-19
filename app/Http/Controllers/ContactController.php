<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Services\SpamGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContactController extends Controller
{
    private const PER_IP_PER_HOUR = 10;

    private const PER_EMAIL_PER_HOUR = 3;

    public function show(Request $request): View
    {
        return view('contact.show', [
            'topics' => config('contact.topics'),
            'user' => $request->user(),
        ]);
    }

    public function store(Request $request, SpamGuard $spamGuard): RedirectResponse
    {
        $verdict = $spamGuard->inspect($request);

        // A filled honeypot means a bot: pretend it worked so it doesn't adapt.
        if ($verdict === 'honeypot') {
            return $this->thanks();
        }

        if ($verdict !== null) {
            return back()->withInput()->withErrors(['form' => match ($verdict) {
                'too_fast' => 'Das ging sehr schnell. Bitte warte einen kurzen Moment und sende die Nachricht dann noch einmal ab.',
                default => 'Das Formular ist abgelaufen. Bitte sende deine Nachricht noch einmal ab.',
            }]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:190'],
            'topic' => ['required', Rule::in(array_keys(config('contact.topics')))],
            'message' => ['required', 'string', 'min:10', 'max:3000', function (string $attribute, mixed $value, \Closure $fail) {
                if (preg_match_all('~https?://|www\.~i', $value) > 2) {
                    $fail('Bitte gib in der Nachricht höchstens zwei Links an.');
                }
            }],
            'privacy' => ['accepted'],
        ], [
            'name.required' => 'Bitte gib deinen Namen an.',
            'name.max' => 'Der Name darf höchstens 100 Zeichen lang sein.',
            'email.required' => 'Bitte gib deine E-Mail-Adresse an, damit wir antworten können.',
            'email.email' => 'Bitte gib eine gültige E-Mail-Adresse an.',
            'topic.required' => 'Bitte wähle ein Thema.',
            'topic.in' => 'Bitte wähle ein Thema aus der Liste.',
            'message.required' => 'Bitte schreib uns deine Nachricht.',
            'message.min' => 'Deine Nachricht ist sehr kurz. Bitte schreib uns mindestens ein paar Worte.',
            'message.max' => 'Die Nachricht darf höchstens 3000 Zeichen lang sein.',
            'privacy.accepted' => 'Bitte bestätige, dass du die Datenschutzerklärung gelesen hast.',
        ]);

        $limits = [
            'contact:ip:'.$request->ip() => self::PER_IP_PER_HOUR,
            'contact:mail:'.sha1(strtolower($validated['email'])) => self::PER_EMAIL_PER_HOUR,
        ];

        foreach ($limits as $key => $maxAttempts) {
            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                return back()->withInput()->withErrors(['form' => 'Du hast in kurzer Zeit sehr viele Nachrichten gesendet. Bitte versuche es später noch einmal.']);
            }
        }

        foreach (array_keys($limits) as $key) {
            RateLimiter::hit($key, 3600);
        }

        // Stored first, so nothing is lost if the mail server is down.
        $message = ContactMessage::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'topic' => $validated['topic'],
            'message' => $validated['message'],
        ]);

        if ($recipient = config('contact.recipient')) {
            try {
                Mail::to($recipient)->send(new ContactMessageReceived($message));
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return $this->thanks();
    }

    private function thanks(): RedirectResponse
    {
        return redirect()->route('contact.show')->with('sent', true);
    }
}
