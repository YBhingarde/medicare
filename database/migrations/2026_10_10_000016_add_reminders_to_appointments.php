<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * reminder_24h_sent_at / reminder_2h_sent_at : set when that reminder was queued (or was not needed,
 * e.g. the visit was confirmed less than 24 h before it). NULL = still to be sent. Reset when the visit is moved.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $t) {
            $t->timestamp('reminder_24h_sent_at')->nullable();
            $t->timestamp('reminder_2h_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $t) {
            $t->dropColumn(['reminder_24h_sent_at', 'reminder_2h_sent_at']);
        });
    }
};
