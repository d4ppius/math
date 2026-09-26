<?php

namespace App\Console\Commands;

use App\Models\Child;
use App\Notifications\PracticeReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

/**
 * Runs every few minutes (routes/console.php), not once a day: each child
 * has their own reminder_time now instead of one fixed time for everyone.
 */
class SendPracticeReminders extends Command
{
    protected $signature = 'reminders:send';

    protected $description = "Nudge children who haven't met today's practice goal yet, at their own reminder time";

    public function handle(): int
    {
        $now = Carbon::now();
        $today = $now->toDateString();
        $isoWeekday = (int) $now->format('N'); // 1 (Monday) .. 7 (Sunday)

        $children = Child::query()
            ->where('active', true)
            ->where('reminders_enabled', true)
            ->where(fn ($query) => $query->whereNull('reminded_at')->orWhereDate('reminded_at', '<', $today))
            ->whereDoesntHave('dailyGoalLogs', fn ($query) => $query->whereDate('date', $today)->where('goal_met', true))
            ->whereHas('pushSubscriptions')
            ->with(['exerciseSettings' => fn ($query) => $query->where('enabled', true)])
            ->get();

        $sent = 0;

        foreach ($children as $child) {
            if (! $this->isDue($child, $now) || ! $this->isPracticeDayFor($child, $isoWeekday)) {
                continue;
            }

            Notification::send($child, new PracticeReminder);
            $child->forceFill(['reminded_at' => $today])->save();
            $sent++;
        }

        $this->info("Sent {$sent} practice reminder(s).");

        return self::SUCCESS;
    }

    /**
     * True for the first ~5 minutes after the child's reminder_time, so a
     * five-minute schedule catches it exactly once. Compared as minutes
     * since midnight (with wraparound) rather than two Carbon instants, so a
     * reminder_time right before midnight isn't missed by the date rolling
     * over between one scheduler tick and the next.
     */
    private function isDue(Child $child, Carbon $now): bool
    {
        [$hour, $minute] = array_map('intval', explode(':', $child->reminder_time));

        $nowMinutes = $now->hour * 60 + $now->minute;
        $targetMinutes = $hour * 60 + $minute;

        return (($nowMinutes - $targetMinutes + 1440) % 1440) < 5;
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
