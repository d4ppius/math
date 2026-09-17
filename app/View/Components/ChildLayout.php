<?php

namespace App\View\Components;

use App\Models\Child;
use Illuminate\View\Component;
use Illuminate\View\View;

class ChildLayout extends Component
{
    /**
     * $token is the plaintext magic-link token — only available on the
     * initial /k/{token} request, never persisted. When present, the
     * layout links this child's own per-child manifest (start_url =
     * /k/{token}) so "Add to Home Screen" installs a PWA that re-opens
     * the magic link itself, instead of losing it to a generic start_url.
     */
    public function __construct(public ?Child $child = null, public ?string $token = null) {}

    public function render(): View
    {
        return view('layouts.child');
    }
}
