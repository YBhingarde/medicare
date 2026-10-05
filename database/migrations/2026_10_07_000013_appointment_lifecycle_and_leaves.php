<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - appointments.meeting_link  : video-call link for Online visits (set by the doctor)
 * - appointments.cancelled_by  : patient / doctor / admin / system
 * - doctor_leaves              : days a doctor is on leave (no bookings are possible on these dates)
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $t) {
            $t->string('meeting_link')->nullable()->after('mode');
            $t->string('cancelled_by', 20)->nullable()->after('status');
        });

        Schema::create('doctor_leaves', function (Blueprint $t) {
            $t->id();
            $t->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $t->date('date');
            $t->string('reason')->nullable();
            $t->timestamps();
            $t->unique(['doctor_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_leaves');
        Schema::table('appointments', function (Blueprint $t) {
            $t->dropColumn(['meeting_link', 'cancelled_by']);
        });
    }
};
