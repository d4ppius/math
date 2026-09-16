<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'timezone'])]
class Family extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Family $family) {
            $family->invite_token ??= Str::random(32);
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(Child::class);
    }

    public function regenerateInviteToken(): string
    {
        $this->invite_token = Str::random(32);
        $this->save();

        return $this->invite_token;
    }
}
