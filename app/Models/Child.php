<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use App\Services\IconGenerator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\HasPushSubscriptions;

#[Fillable(['family_id', 'name', 'avatar', 'color_theme', 'active'])]
#[Hidden(['login_token_hash', 'pin_hash', 'remember_token'])]
class Child extends Model implements AuthenticatableContract
{
    use Authenticatable, HasFactory, HasPushSubscriptions, Notifiable;

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function exerciseSettings(): HasMany
    {
        return $this->hasMany(ChildExerciseSetting::class);
    }

    public function factStats(): HasMany
    {
        return $this->hasMany(ChildFactStat::class);
    }

    public function practiceSessions(): HasMany
    {
        return $this->hasMany(PracticeSession::class);
    }

    public function dailyGoalLogs(): HasMany
    {
        return $this->hasMany(DailyGoalLog::class);
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'child_badges')->withPivot('earned_at');
    }

    /**
     * Generates a new plaintext login token, stores its hash, and returns the
     * plaintext so the caller can build the child's personal magic link.
     */
    public function generateLoginToken(): string
    {
        $plainToken = Str::random(48);

        $this->login_token_hash = hash('sha256', $plainToken);
        $this->save();

        return $plainToken;
    }

    public function loginTokenMatches(string $plainToken): bool
    {
        return hash_equals($this->login_token_hash, hash('sha256', $plainToken));
    }

    public function setPin(?string $pin): void
    {
        $this->pin_hash = $pin ? bcrypt($pin) : null;
        $this->save();
    }

    public function pinMatches(string $pin): bool
    {
        return $this->pin_hash && Hash::check($pin, $this->pin_hash);
    }

    /**
     * URL of this child's home-screen icon. The version query busts the
     * (very sticky) iOS icon caches whenever the artwork changes.
     */
    public function iconUrl(int $size): string
    {
        return route('child.icon', ['child' => $this, 'size' => $size, 'v' => IconGenerator::VERSION]);
    }

    public function requiresPin(): bool
    {
        return ! empty($this->pin_hash);
    }
}
