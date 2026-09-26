<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\ImpersonationController;
use App\Models\Child;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Lets a parent (their own children) or an admin (any child) see exactly
 * what a child sees, without needing its magic link: logs the current
 * web-guard user into the separate child guard, the same server-side-only
 * pattern ImpersonationController already uses for admin -> parent.
 *
 * A practice session started while this is active is flagged
 * (PracticeSession::is_preview) so nothing it writes touches the child's
 * real progress — see AttemptRecorder and PracticeSessionController.
 */
class ChildPreviewController extends Controller
{
    public const SESSION_KEY = 'previewer_id';

    public const RETURN_URL_KEY = 'preview_return_url';

    public function start(Request $request, Child $child): RedirectResponse
    {
        $this->authorize('view', $child);

        // No nesting: keeping track of two different ways back at once
        // isn't worth the complexity, so the first one has to end first.
        if ($request->session()->has(self::SESSION_KEY) || $request->session()->has(ImpersonationController::SESSION_KEY)) {
            return back()->with('error', 'Es läuft schon eine Vorschau oder Anmeldung als jemand anderes. Erst dort beenden.');
        }

        $request->session()->put(self::SESSION_KEY, $request->user()->id);
        $request->session()->put(self::RETURN_URL_KEY, url()->previous());

        Auth::guard('child')->login($child);
        $request->session()->regenerate();

        return redirect()->route('child.home');
    }

    public function stop(Request $request): RedirectResponse
    {
        $user = User::whereKey($request->session()->get(self::SESSION_KEY))->first();

        abort_unless($user, 403);

        $returnUrl = $request->session()->pull(self::RETURN_URL_KEY) ?? route('dashboard');
        $request->session()->forget(self::SESSION_KEY);

        Auth::guard('child')->logout();
        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->to($returnUrl);
    }
}
