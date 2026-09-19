<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public const SESSION_KEY = 'impersonator_id';

    public function start(Request $request, User $user): RedirectResponse
    {
        if ($user->is_admin) {
            return back()->with('error', 'Administratoren können nicht übernommen werden.');
        }

        // Kept server-side in the session, so the way back can't be forged.
        $request->session()->put(self::SESSION_KEY, $request->user()->id);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function stop(Request $request): RedirectResponse
    {
        $admin = User::whereKey($request->session()->pull(self::SESSION_KEY))
            ->where('is_admin', true)
            ->first();

        abort_unless($admin, 403);

        Auth::guard('web')->login($admin);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }
}
