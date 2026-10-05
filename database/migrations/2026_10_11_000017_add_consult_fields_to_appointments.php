<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * consult_started_at : the doctor pressed "Start consultation" (the patient can now enter the video room)
 * consult_joined_at  : the patient entered the room (status becomes "in_progress")
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $t) {
            $t->timestamp('consult_started_at')->nullable();
            $t->timestamp('consult_joined_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $t) {
            $t->dropColumn(['consult_started_at', 'consult_joined_at']);
        });
    }
};
