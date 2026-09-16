<?php

namespace App\Events;

use App\Models\PracticeSession;
use Illuminate\Foundation\Events\Dispatchable;

class PracticeSessionCompleted
{
    use Dispatchable;

    public function __construct(public PracticeSession $session) {}
}
