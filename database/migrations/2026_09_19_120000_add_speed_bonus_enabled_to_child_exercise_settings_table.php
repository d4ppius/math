<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('child_exercise_settings', function (Blueprint $table) {
            $table->boolean('speed_bonus_enabled')->default(true)->after('show_timer');
        });
    }

    public function down(): void
    {
        Schema::table('child_exercise_settings', function (Blueprint $table) {
            $table->dropColumn('speed_bonus_enabled');
        });
    }
};
