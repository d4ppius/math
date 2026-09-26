<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('children', function (Blueprint $table) {
            // Defaults match today's fixed behaviour exactly (on, 17:00), so
            // nothing changes for an existing child until a parent touches it.
            $table->boolean('reminders_enabled')->default(true)->after('show_locked_badges');
            $table->time('reminder_time')->default('17:00:00')->after('reminders_enabled');
            // Set once a reminder goes out today; keeps SendPracticeReminders
            // (now running every few minutes instead of once a day) from
            // sending a second one if its time window is ever hit twice.
            $table->date('reminded_at')->nullable()->after('reminder_time');
        });
    }

    public function down(): void
    {
        Schema::table('children', function (Blueprint $table) {
            $table->dropColumn(['reminders_enabled', 'reminder_time', 'reminded_at']);
        });
    }
};
