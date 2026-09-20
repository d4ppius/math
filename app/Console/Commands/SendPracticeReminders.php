<?php

namespace App\Console\Commands;

use App\Models\Child;
use App\Notifications\PracticeReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendPracticeReminders extends Command
{
    protected $signature = 'reminders:send';

    protected $description = "Nudge children who haven't met today's practice goal yet and are subscribed to push";

    public function handle(): int
    {
        $today = now()->toDateString();
        $isoWeekday = (int) now()->format('N'); // 1 (Monday) .. 7 (Sunday)

        $children = Child::query()
            ->where('active', true)
            ->whereDoesntHave('dailyGoalLogs', fn ($query) => $query->whereDate('date', $today)->where('goal_met', true))
            ->whereHas('pushSubscriptions')
            ->with(['exerciseSettings' => fn ($query) => $query->where('enabled', true)])
            ->get();

        $sent = 0;

        foreach ($children as $child) {
            if (! $this->isPracticeDayFor($child, $isoWeekday)) {
                continue;
            }

            Notification::send($child, new PracticeReminder);
            $sent++;
        }

        $this->info("Sent {$sent} practice reminder(s).");

        return self::SUCCESS;
    }

    private function isPracticeDayFor(Child $child, int $isoWeekday): bool
    {
        foreach ($child->exerciseSettings as $setting) {
            $isPracticeDay = match ($setting->target_frequency) {
                'daily' => true,
                'weekdays' => $isoWeekday <= 5,
                'custom' => empty($setting->target_days) || in_array($isoWeekday, $setting->target_days, true),
                default => true,
            };

            if ($isPracticeDay) {
                return true;
            }
        }

        return false;
    }
}
