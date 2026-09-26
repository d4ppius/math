<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * A one-off push notification an admin writes and sends by hand (see
 * Admin\PushController), to a specific parent or child, or to all of one
 * group. Web-Push only, no mail — title and body are whatever the admin
 * typed, not fixed content like the automatic reminders.
 */
class AdminBroadcast extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $title, public string $body) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, self $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->icon('/images/icons/icon-192.png')
            ->body($this->body);
    }
}
