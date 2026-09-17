<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Retroactive fix for deployments where create_practice_sessions_table had
 * already run (successfully) before started_at/ended_at were switched from
 * timestamp() to dateTime() — that earlier migration left started_at as
 * `timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`
 * (MySQL's legacy "first timestamp column" auto-assignment), silently
 * resetting it to "now" on every UPDATE to the row, e.g. every answered
 * question. A fresh install already gets the correct column via the
 * updated create_practice_sessions_table migration, so this is a no-op
 * there (and irrelevant on sqlite, which has no such column-level default).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (! $this->hasAutoUpdateDefault('practice_sessions', 'started_at')) {
            return;
        }

        DB::statement('ALTER TABLE practice_sessions MODIFY started_at DATETIME NOT NULL');
        DB::statement('ALTER TABLE practice_sessions MODIFY ended_at DATETIME NULL DEFAULT NULL');
        DB::statement('ALTER TABLE practice_sessions MODIFY current_question_issued_at DATETIME NULL DEFAULT NULL');
    }

    public function down(): void
    {
        // Deliberately no rollback: reverting to a timestamp column with an
        // implicit MySQL-assigned ON UPDATE CURRENT_TIMESTAMP is the bug
        // this migration exists to remove.
    }

    private function hasAutoUpdateDefault(string $table, string $column): bool
    {
        $row = DB::selectOne(
            'SELECT EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        );

        return $row && str_contains(strtolower($row->EXTRA ?? ''), 'on update');
    }
};
