<?php

namespace App\Http\Controllers\Child;

use App\Http\Controllers\Controller;
use App\Models\Child;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ChildAuthController extends Controller
{
    /**
     * Entry point for a child's personal magic-link home-screen icon.
     *
     * Deliberately never redirects away from /k/{token}: that URL is what
     * gets saved when a parent taps "Add to Home Screen" in Safari, so it
     * must render the actual destination (home, or the PIN form) directly
     * — a redirect would make the bookmark point at a generic, token-less
     * URL that stops working once the session expires.
     */
    public function loginViaToken(Request $request, string $token): View
    {
        $child = $this->findChildOrFail($token);

        if ($child->requiresPin()) {
            return view('child.pin', ['token' => $token, 'child' => $child]);
        }

        $this->login($request, $child);

        return view('child.home', ['child' => $child, 'token' => $token]);
    }

    public function verifyPin(Request $request, string $token): View
    {
        $child = $this->findChildOrFail($token);

        $request->validate(['pin' => ['required', 'digits:4']]);

        if (! $child->pinMatches($request->input('pin'))) {
            return view('child.pin', [
                'token' => $token,
                'child' => $child,
                'pinError' => 'Falscher Code, versuch es nochmal.',
            ]);
        }

        $this->login($request, $child);

        return view('child.home', ['child' => $child, 'token' => $token]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('child')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function findChildOrFail(string $token): Child
    {
        $tokenHash = hash('sha256', $token);

        return Child::where('login_token_hash', $tokenHash)->where('active', true)->firstOr(function () {
            abort(404);
        });
    }

    private function login(Request $request, Child $child): void
    {
        $child->forceFill(['last_seen_at' => now()])->save();

        Auth::guard('child')->login($child, remember: true);

        $request->session()->regenerate();
    }
}
