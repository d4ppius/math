<?php

namespace App\Notifications;

use App\Models\Child;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * A friendly nudge sent directly to a child (not the parents) when they
 * haven't practiced yet today. Web-Push only — kids don't have an email
 * address, and this only reaches them once their homescreen icon has
 * subscribed (see ChildPushSubscriptionController).
 */
class PracticeReminder extends Notification implements ShouldQueue
{
    use Queueable;

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(Child $notifiable, self $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('🦊 Zeit zum Üben!')
            ->icon(route('child.icon', ['child' => $notifiable, 'size' => 192]))
            ->body('Lust auf ein paar Runden Einmaleins?')
            ->action('Los geht\'s', 'practice')
            ->data(['url' => route('child.home')]);
    }
}
