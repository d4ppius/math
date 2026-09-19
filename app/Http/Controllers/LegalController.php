<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class LegalController extends Controller
{
    public function imprint(): View
    {
        return view('legal.imprint');
    }

    public function privacy(): View
    {
        return view('legal.privacy');
    }
}
