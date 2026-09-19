<?php

namespace App\Listeners;

use App\Events\PracticeSessionCompleted;
use App\Services\Gamification\BadgeEvaluator;

class EvaluateBadges
{
    public function __construct(private BadgeEvaluator $evaluator) {}

    public function handle(PracticeSessionCompleted $event): void
    {
        // Newly earned badges are not passed along: the summary page reads
        // them back from child_badges.earned_at, so a reload still shows them.
        $this->evaluator->evaluate($event->session->child, $event->session);
    }
}
