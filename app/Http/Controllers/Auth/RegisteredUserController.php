<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
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
    public function store(Request $request): RedirectResponse
    {
        $invitingFamily = $request->filled('invite_token')
            ? Family::where('invite_token', $request->input('invite_token'))->first()
            : null;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];

        if (! $invitingFamily) {
            $rules['family_name'] = ['required', 'string', 'max:255'];
        }

        $validated = $request->validate($rules);

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
