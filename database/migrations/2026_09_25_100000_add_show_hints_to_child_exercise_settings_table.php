<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('child_exercise_settings', function (Blueprint $table) {
            // Off by default: the strategy hints are pedagogically standard,
            // but not matched to any specific school curriculum, so parents
            // opt in after checking whether it helps or confuses their child.
            $table->boolean('show_hints')->default(false)->after('speed_bonus_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('child_exercise_settings', function (Blueprint $table) {
            $table->dropColumn('show_hints');
        });
    }
};
