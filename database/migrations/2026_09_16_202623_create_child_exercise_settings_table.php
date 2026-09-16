<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('child_exercise_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_type_id')->constrained()->cascadeOnDelete();
            $table->json('active_groups');
            $table->unsignedSmallInteger('session_duration_minutes')->default(10);
            $table->enum('target_frequency', ['daily', 'weekdays', 'custom'])->default('daily');
            $table->json('target_days')->nullable();
            $table->boolean('sound_enabled')->default(true);
            $table->timestamps();

            $table->unique(['child_id', 'exercise_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_exercise_settings');
    }
};
