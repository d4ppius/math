<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('practice_sessions', function (Blueprint $table) {
            // Set for a session started via ChildPreviewController: everything
            // it writes stays local to its own row, so it never reaches
            // ChildFactStat, SessionAttempt, points, badges or the daily goal.
            // See AttemptRecorder and PracticeSessionController::completeSession().
            $table->boolean('is_preview')->default(false)->after('current_question_issued_at');
        });
    }

    public function down(): void
    {
        Schema::table('practice_sessions', function (Blueprint $table) {
            $table->dropColumn('is_preview');
        });
    }
};
