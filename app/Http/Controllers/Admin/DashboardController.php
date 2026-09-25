<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Models\ContactMessage;
use App\Models\Family;
use App\Models\PracticeSession;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'openMessages' => ContactMessage::whereNull('handled_at')->count(),
            'counts' => [
                'families' => Family::count(),
                'parents' => User::count(),
                'children' => Child::count(),
                // Both deliberately exclude previews (ChildPreviewController):
                // an admin or parent trying one out shouldn't inflate either
                // the lifetime session count or "how many kids practiced today".
                'sessions' => PracticeSession::where('is_preview', false)->count(),
                'practicedToday' => PracticeSession::whereDate('started_at', today())->where('is_preview', false)->distinct()->count('child_id'),
            ],
            'recentSessions' => PracticeSession::with(['child.family', 'exerciseType'])
                ->latest('started_at')
                ->limit(10)
                ->get(),
        ]);
    }
}
