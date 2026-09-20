<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing rows are all Einmaleins and stay on. A new exercise is created
        // per child with its own default (see ExerciseTypeContract::enabledByDefault()).
        Schema::table('child_exercise_settings', function (Blueprint $table) {
            $table->boolean('enabled')->default(true)->after('exercise_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('child_exercise_settings', function (Blueprint $table) {
            $table->dropColumn('enabled');
        });
    }
};
