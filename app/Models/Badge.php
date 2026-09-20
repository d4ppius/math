<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['key', 'name', 'description', 'icon', 'criteria'])]
class Badge extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'criteria' => 'array',
        ];
    }

    /**
     * Artwork for this badge, if any: public/images/badges/{key}.png, else a
     * shared public/images/badges/{criteria.type}.png. Null means "use the
     * emoji in `icon`".
     */
    public function imageUrl(): ?string
    {
        $folder = trim(config('badges.images_path'), '/');

        foreach ([$this->key, $this->criteria['type'] ?? null] as $name) {
            if ($name && is_file(public_path("{$folder}/{$name}.png"))) {
                return asset("{$folder}/{$name}.png").'?v='.filemtime(public_path("{$folder}/{$name}.png"));
            }
        }

        return null;
    }

    /** The exercise this badge belongs to (e.g. "addition"), or null if it is general. */
    public function exerciseKey(): ?string
    {
        return $this->criteria['exercise_type'] ?? null;
    }

    /**
     * Whether this child can ever earn the badge: its exercise must be switched
     * on for them, the speed badge needs the speed bonus of that exercise, and the
     * all-rounder needs at least two exercises. Earned badges are shown regardless
     * (see Child::attainableBadges()).
     *
     * @param  array{available: list<string>, speedBonus: array<string, bool>}|null  $context  from Child::badgeExerciseContext(), pass it when checking many badges
     */
    public function isAttainableBy(Child $child, ?array $context = null): bool
    {
        $context ??= $child->badgeExerciseContext();

        $type = $this->criteria['type'] ?? null;
        $exercise = $this->exerciseKey();

        if ($type === 'all_exercises_same_day') {
            return count($context['available']) >= ($this->criteria['min_exercises'] ?? 2);
        }

        if ($exercise !== null && ! in_array($exercise, $context['available'], true)) {
            return false;
        }

        if ($type === 'blitz') {
            return $exercise !== null ? ($context['speedBonus'][$exercise] ?? false) : $child->hasSpeedBonus();
        }

        return true;
    }

    /** The multiplication row a row-mastery badge belongs to. */
    public function rowNumber(): ?int
    {
        return ($this->criteria['type'] ?? null) === 'row_mastery' ? ($this->criteria['difficulty_group'] ?? null) : null;
    }

    /** The short mark shown on top of a shared image: the row, or the group's own mark. */
    public function overlay(): ?string
    {
        $type = $this->criteria['type'] ?? null;

        return match ($type) {
            'row_mastery' => isset($this->criteria['difficulty_group']) ? (string) $this->criteria['difficulty_group'] : null,
            'group_mastery' => $this->criteria['overlay'] ?? null,
            default => null,
        };
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(Child::class, 'child_badges')->withPivot('earned_at');
    }
}
