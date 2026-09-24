<?php

namespace App\Models;

use App\Services\ExerciseTypes\ExerciseTypeRegistry;
use App\Services\IconGenerator;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\HasPushSubscriptions;

#[Fillable(['family_id', 'name', 'avatar', 'color_theme', 'active', 'show_locked_badges'])]
#[Hidden(['login_token_hash', 'pin_hash', 'remember_token'])]
class Child extends Model implements AuthenticatableContract
{
    use Authenticatable, HasFactory, HasPushSubscriptions, Notifiable;

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'show_locked_badges' => 'boolean',
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

    /**
     * Badges earned since the given session started, i.e. by that session
     * (sessions never overlap). Derived from the database so it survives reloads.
     */
    public function badgesEarnedDuring(PracticeSession $session): Collection
    {
        return $this->badges()->wherePivot('earned_at', '>=', $session->started_at)->get();
    }

    /** Whether any of the child's exercises awards a speed bonus (true if none are set up yet). */
    public function hasSpeedBonus(): bool
    {
        $settings = $this->exerciseSettings;

        return $settings->isEmpty() || $settings->contains('speed_bonus_enabled', true);
    }

    /**
     * Every badge this child has earned or can still work towards, in catalogue
     * order. Badges that can never be earned by this child (the speed badge
     * when the speed bonus is off) are left out, so nothing unreachable is shown.
     *
     * @return Collection<int, Badge>
     */
    public function attainableBadges(): Collection
    {
        $earnedIds = $this->badges()->pluck('badges.id');
        $context = $this->badgeExerciseContext();

        return Badge::orderBy('id')->get()
            ->filter(fn (Badge $badge) => $earnedIds->contains($badge->id) || $badge->isAttainableBy($this, $context))
            ->values();
    }

    /**
     * Which exercises count for the child's badges: the switched-on, active ones,
     * with whether each awards a speed bonus. A child without any settings yet
     * counts as set up with the defaults.
     *
     * @return array{available: list<string>, speedBonus: array<string, bool>}
     */
    public function badgeExerciseContext(): array
    {
        $settings = $this->exerciseSettings()->with('exerciseType')->get();

        if ($settings->isEmpty()) {
            $registry = app(ExerciseTypeRegistry::class);
            $keys = collect(array_keys(config('exercise_types', [])))
                ->filter(fn (string $key) => $registry->get($key)->enabledByDefault())
                ->values();

            return ['available' => $keys->all(), 'speedBonus' => $keys->mapWithKeys(fn (string $key) => [$key => true])->all()];
        }

        $enabled = $settings->filter(fn (ChildExerciseSetting $setting) => $setting->enabled && $setting->exerciseType?->is_active);

        return [
            'available' => $enabled->map(fn (ChildExerciseSetting $setting) => $setting->exerciseType->key)->values()->all(),
            'speedBonus' => $enabled->mapWithKeys(fn (ChildExerciseSetting $setting) => [$setting->exerciseType->key => (bool) $setting->speed_bonus_enabled])->all(),
        ];
    }

    /**
     * The exercises this child can choose from: switched on by the parents, not
     * switched off globally by an admin, and still registered. Each setting carries
     * its exerciseType and, as `implementation`, the exercise class.
     *
     * @return \Illuminate\Support\Collection<int, ChildExerciseSetting>
     */
    public function availableExercises(): \Illuminate\Support\Collection
    {
        $registry = app(ExerciseTypeRegistry::class);
        $registered = array_keys(config('exercise_types', []));

        return $this->exerciseSettings()
            ->where('enabled', true)
            ->with('exerciseType')
            ->orderBy('exercise_type_id')
            ->get()
            ->filter(fn (ChildExerciseSetting $setting) => $setting->exerciseType?->is_active && in_array($setting->exerciseType->key, $registered, true))
            ->each(fn (ChildExerciseSetting $setting) => $setting->implementation = $registry->get($setting->exerciseType->key))
            ->values();
    }

    /**
     * One entry per exercise this child has a level in: currently available
     * ones, plus any switched off since that still hold points, so earned
     * progress never simply disappears (mirrors how earned badges stay visible
     * after an exercise is turned off — see attainableBadges()).
     *
     * @return \Illuminate\Support\Collection<int, ChildExerciseSetting>
     */
    public function exercisesWithLevel(): \Illuminate\Support\Collection
    {
        $registered = array_keys(config('exercise_types', []));

        return $this->exerciseSettings()
            ->with('exerciseType')
            ->get()
            ->filter(fn (ChildExerciseSetting $setting) => $setting->exerciseType && in_array($setting->exerciseType->key, $registered, true))
            ->filter(fn (ChildExerciseSetting $setting) => ($setting->enabled && $setting->exerciseType->is_active) || $setting->points > 0)
            ->sortBy(fn (ChildExerciseSetting $setting) => array_search($setting->exerciseType->key, $registered, true))
            ->values();
    }

    /** A running session of this exercise with time left, if the child paused one. */
    public function resumableSessionFor(int $exerciseTypeId): ?PracticeSession
    {
        $session = $this->practiceSessions()
            ->where('exercise_type_id', $exerciseTypeId)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        return $session?->hasTimeRemaining() ? $session : null;
    }

    public function requiresPin(): bool
    {
        return ! empty($this->pin_hash);
    }
}
