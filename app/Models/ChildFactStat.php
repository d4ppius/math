<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'child_id', 'fact_id', 'attempts_total', 'attempts_correct', 'avg_response_ms',
    'last_response_ms', 'current_streak', 'last_practiced_at', 'last_result', 'priority_score',
])]
class ChildFactStat extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'last_practiced_at' => 'datetime',
            'last_result' => 'boolean',
            'priority_score' => 'float',
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function fact(): BelongsTo
    {
        return $this->belongsTo(Fact::class);
    }

    public function accuracy(): float
    {
        return $this->attempts_total > 0
            ? $this->attempts_correct / $this->attempts_total
            : 0.0;
    }

    /**
     * A simple mastery status for the parent heatmap: 'unseen' when the
     * child hasn't been asked this fact yet, otherwise one of the fixed
     * status roles good/warning/serious/critical based on accuracy.
     */
    public function masteryStatus(): string
    {
        if ($this->attempts_total === 0) {
            return 'unseen';
        }

        $accuracy = $this->accuracy();

        return match (true) {
            $accuracy >= 0.85 => 'good',
            $accuracy >= 0.6 => 'warning',
            $accuracy >= 0.35 => 'serious',
            default => 'critical',
        };
    }
}
