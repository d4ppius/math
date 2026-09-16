<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $family = $request->user()->family;

        return view('parent.dashboard', [
            'family' => $family,
            'children' => $family->children()->orderBy('name')->get(),
            'inviteUrl' => route('register', ['invite' => $family->invite_token]),
        ]);
    }
}
