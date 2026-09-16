<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercise_type_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('operand_a');
            $table->unsignedTinyInteger('operand_b');
            $table->integer('correct_answer');
            $table->unsignedTinyInteger('difficulty_group');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['exercise_type_id', 'operand_a', 'operand_b']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facts');
    }
};
