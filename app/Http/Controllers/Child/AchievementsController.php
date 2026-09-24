<?php

namespace App\Http\Controllers\Child;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The child's own "statistics" page: level, points and badges. Kept off the
 * home screen so that stays uncluttered.
 */
class AchievementsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $child = $request->user('child');

        $badges = $child->attainableBadges();
        $earned = $child->badges->keyBy('id');

        return view('child.achievements', [
            'child' => $child,
            'exerciseLevels' => $child->exercisesWithLevel(),
            'earned' => $earned,
            'earnedBadges' => $badges->filter(fn ($badge) => $earned->has($badge->id)),
            // Only when the parents allow it: what is still out there to earn.
            'lockedBadges' => $child->show_locked_badges
                ? $badges->reject(fn ($badge) => $earned->has($badge->id))
                : collect(),
            'total' => $child->show_locked_badges ? $badges->count() : $earned->count(),
        ]);
    }
}
