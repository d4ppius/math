<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('child_fact_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fact_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('attempts_total')->default(0);
            $table->unsignedInteger('attempts_correct')->default(0);
            $table->unsignedInteger('avg_response_ms')->default(0);
            $table->unsignedInteger('last_response_ms')->nullable();
            $table->unsignedInteger('current_streak')->default(0);
            $table->timestamp('last_practiced_at')->nullable();
            $table->boolean('last_result')->nullable();
            $table->float('priority_score')->default(1);
            $table->timestamps();

            $table->unique(['child_id', 'fact_id']);
            $table->index('priority_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_fact_stats');
    }
};
