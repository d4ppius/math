<?php

namespace App\Models;

use App\Services\Gamification\LevelCalculator;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['child_id', 'exercise_type_id', 'enabled', 'active_groups', 'session_duration_minutes', 'target_frequency', 'target_days', 'sound_enabled', 'show_timer', 'speed_bonus_enabled', 'points'])]
class ChildExerciseSetting extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'active_groups' => 'array',
            'target_days' => 'array',
            'sound_enabled' => 'boolean',
            'show_timer' => 'boolean',
            'speed_bonus_enabled' => 'boolean',
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function exerciseType(): BelongsTo
    {
        return $this->belongsTo(ExerciseType::class);
    }

    /** @return array<string, mixed> See LevelCalculator::forPoints(). */
    public function level(): array
    {
        return app(LevelCalculator::class)->forPoints($this->points);
    }
}
