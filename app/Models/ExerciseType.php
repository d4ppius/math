<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['key', 'name', 'config_schema', 'is_active'])]
class ExerciseType extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'config_schema' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function facts(): HasMany
    {
        return $this->hasMany(Fact::class);
    }

    public function childExerciseSettings(): HasMany
    {
        return $this->hasMany(ChildExerciseSetting::class);
    }
}
