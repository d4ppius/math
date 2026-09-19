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
        foreach ([$this->key, $this->criteria['type'] ?? null] as $name) {
            if ($name && is_file(public_path("images/badges/{$name}.png"))) {
                return asset("images/badges/{$name}.png").'?v='.filemtime(public_path("images/badges/{$name}.png"));
            }
        }

        return null;
    }

    /** The multiplication row a row-mastery badge belongs to (shown on top of a shared image). */
    public function rowNumber(): ?int
    {
        return ($this->criteria['type'] ?? null) === 'row_mastery' ? ($this->criteria['difficulty_group'] ?? null) : null;
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(Child::class, 'child_badges')->withPivot('earned_at');
    }
}
