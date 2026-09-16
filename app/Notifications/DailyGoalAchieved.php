<?php

namespace App\Notifications;

use App\Models\Child;
use App\Models\PracticeSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class DailyGoalAchieved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Child $child, public PracticeSession $session) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', WebPushChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $accuracy = $this->session->questions_answered > 0
            ? round($this->session->questions_correct / $this->session->questions_answered * 100)
            : 0;

        return (new MailMessage)
            ->subject("🎉 {$this->child->name} hat heute geübt!")
            ->greeting("Hallo {$notifiable->name}!")
            ->line("{$this->child->name} hat die heutige Einmaleins-Übung geschafft.")
            ->line("Ergebnis: {$this->session->total_points} Punkte, {$accuracy}% richtig beantwortet.")
            ->action('Statistik ansehen', route('parent.children.statistics', $this->child))
            ->line('Weiter so!')
            ->salutation('Viele Grüsse, dein 1x1 Trainer');
    }

    public function toWebPush(object $notifiable, self $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title("🎉 {$this->child->name} hat geübt!")
            ->icon('/images/icons/icon-192.png')
            ->body("{$this->session->total_points} Punkte heute – weiter so!")
            ->action('Ansehen', 'view')
            ->data(['url' => route('parent.children.statistics', $this->child)]);
    }
}
