<?php

namespace App\Services\ExerciseTypes\Concerns;

/**
 * Default heatmap header labels for the usual two-operand exercises: the
 * operator next to the operand, e.g. "×8" for a column and "7×" for a row.
 * Exercises with a different grid (doubling/halving) override these.
 */
trait DefaultGridLabels
{
    public function heatmapColumnLabel(int $col): string
    {
        return $this->operator().$col;
    }

    public function heatmapRowLabel(int $row): string
    {
        return $row.$this->operator();
    }
}
