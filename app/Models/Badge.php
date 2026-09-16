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

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(Child::class, 'child_badges')->withPivot('earned_at');
    }
}
