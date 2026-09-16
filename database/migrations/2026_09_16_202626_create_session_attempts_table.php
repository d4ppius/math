<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fact_id')->constrained()->cascadeOnDelete();
            $table->integer('given_answer')->nullable();
            $table->boolean('is_correct');
            $table->unsignedInteger('response_time_ms');
            $table->integer('points_awarded')->default(0);
            $table->timestamp('question_issued_at');
            $table->timestamp('answered_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_attempts');
    }
};
