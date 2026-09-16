<?php

namespace App\View\Components;

use App\Models\Child;
use Illuminate\View\Component;
use Illuminate\View\View;

class ChildLayout extends Component
{
    public function __construct(public ?Child $child = null) {}

    public function render(): View
    {
        return view('layouts.child');
    }
}
