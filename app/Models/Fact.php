<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['exercise_type_id', 'operand_a', 'operand_b', 'correct_answer', 'difficulty_group', 'metadata'])]
class Fact extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function exerciseType(): BelongsTo
    {
        return $this->belongsTo(ExerciseType::class);
    }

    public function childFactStats(): HasMany
    {
        return $this->hasMany(ChildFactStat::class);
    }

    public function prompt(): string
    {
        return "{$this->operand_a} × {$this->operand_b}";
    }
}
