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

        // Its children's and users' rows cascade away at the database level
        // (foreign keys), which never fires their own model events — so a
        // family delete would otherwise leave their push subscriptions
        // behind as orphaned rows (see Child/User's own deleting event for
        // the other half: deleting one of them directly, not via a family).
        static::deleting(function (Family $family) {
            $family->children->each(fn (Child $child) => $child->pushSubscriptions()->delete());
            $family->users->each(fn (User $user) => $user->pushSubscriptions()->delete());
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
