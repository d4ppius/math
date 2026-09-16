<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practice_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_type_id')->constrained()->cascadeOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->unsignedSmallInteger('planned_duration_seconds');
            $table->enum('status', ['active', 'completed', 'aborted'])->default('active');
            $table->unsignedInteger('total_points')->default(0);
            $table->unsignedInteger('questions_answered')->default(0);
            $table->unsignedInteger('questions_correct')->default(0);
            $table->foreignId('current_fact_id')->nullable()->constrained('facts')->nullOnDelete();
            $table->dateTime('current_question_issued_at')->nullable();
            $table->timestamps();

            $table->index(['child_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practice_sessions');
    }
};
