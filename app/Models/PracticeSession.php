<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable([
    'child_id', 'exercise_type_id', 'started_at', 'ended_at', 'planned_duration_seconds',
    'status', 'total_points', 'questions_answered', 'questions_correct',
    'current_fact_id', 'current_question_issued_at', 'is_preview',
])]
class PracticeSession extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'current_question_issued_at' => 'datetime',
            'is_preview' => 'boolean',
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

    public function attempts(): HasMany
    {
        return $this->hasMany(SessionAttempt::class);
    }

    public function currentFact(): BelongsTo
    {
        return $this->belongsTo(Fact::class, 'current_fact_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function endsAt(): Carbon
    {
        return $this->started_at->clone()->addSeconds($this->planned_duration_seconds);
    }

    public function hasTimeRemaining(): bool
    {
        return now()->lessThan($this->endsAt());
    }
}
