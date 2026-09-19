<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\User;
use App\Services\SpamGuard;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    private const REGISTRATIONS_PER_IP_PER_HOUR = 10;

    /**
     * Display the registration view.
     */
    public function create(Request $request): View
    {
        $invitingFamily = $request->filled('invite')
            ? Family::where('invite_token', $request->query('invite'))->first()
            : null;

        return view('auth.register', [
            'invitingFamily' => $invitingFamily,
            'inviteToken' => $request->query('invite'),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request, SpamGuard $spamGuard): RedirectResponse
    {
        $verdict = $spamGuard->inspect($request);

        // A filled honeypot is a bot: send it on its way as if it had worked.
        if ($verdict === 'honeypot') {
            return redirect()->route('login');
        }

        if ($verdict !== null) {
            return back()->withInput()->withErrors(['form' => match ($verdict) {
                'too_fast' => 'Das ging sehr schnell. Bitte warte einen kurzen Moment und drücke dann noch einmal auf «Registrieren».',
                default => 'Das Formular ist abgelaufen. Bitte versuche es noch einmal.',
            }]);
        }

        $rateKey = 'register:'.$request->ip();

        if (RateLimiter::tooManyAttempts($rateKey, self::REGISTRATIONS_PER_IP_PER_HOUR)) {
            return back()->withInput()->withErrors(['form' => 'Von deinem Anschluss aus wurden in kurzer Zeit sehr viele Konten angelegt. Bitte versuche es später noch einmal.']);
        }

        $invitingFamily = $request->filled('invite_token')
            ? Family::where('invite_token', $request->input('invite_token'))->first()
            : null;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'privacy' => ['accepted'],
        ];

        if (! $invitingFamily) {
            $rules['family_name'] = ['required', 'string', 'max:255'];
        }

        $validated = $request->validate($rules, [
            'privacy.accepted' => 'Bitte bestätige, dass du die Datenschutzerklärung gelesen hast.',
        ]);

        RateLimiter::hit($rateKey, 3600);

        $family = $invitingFamily ?? Family::create(['name' => $validated['family_name']]);

        $user = User::create([
            'family_id' => $family->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
