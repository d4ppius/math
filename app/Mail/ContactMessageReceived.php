<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ContactMessageReceived extends Mailable
{
    public function __construct(public ContactMessage $contactMessage) {}

    public function envelope(): Envelope
    {
        // Sent from the app's own address; the visitor's address is only the
        // Reply-To, so mail providers don't treat this as spoofing.
        return new Envelope(
            replyTo: [new Address($this->contactMessage->email, $this->oneLine($this->contactMessage->name))],
            subject: '['.config('app.name').'] '.$this->contactMessage->topicLabel().' von '.$this->oneLine($this->contactMessage->name),
        );
    }

    public function content(): Content
    {
        return new Content(text: 'mail.contact-message');
    }

    private function oneLine(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value));
    }
}
