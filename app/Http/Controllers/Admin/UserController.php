<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Das eigene Konto kann nicht gelöscht werden.');
        }

        if ($user->is_admin) {
            return back()->with('error', 'Administratoren können nicht gelöscht werden. Zuerst die Admin-Rechte mit `php artisan app:make-admin --revoke` entziehen.');
        }

        $user->delete();

        return redirect()->route('admin.families.show', $user->family_id)->with('status', 'Eltern-Konto wurde gelöscht.');
    }
}
