<?php

namespace App\Http\Controllers\Child;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChildHomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $child = $request->user('child');

        return view('child.home', [
            'child' => $child,
            'pushSubscribed' => $child->pushSubscriptions()->exists(),
        ]);
    }
}
