<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'email', 'topic', 'message', 'handled_at'])]
class ContactMessage extends Model
{
    protected function casts(): array
    {
        return ['handled_at' => 'datetime'];
    }

    public function topicLabel(): string
    {
        return config("contact.topics.{$this->topic}", $this->topic);
    }

    public function isHandled(): bool
    {
        return $this->handled_at !== null;
    }
}
