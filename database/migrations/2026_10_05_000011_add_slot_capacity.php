<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Several patients can book the same time slot (default 1 per slot, set per doctor).
 * The old unique (doctor, date, time) key allowed only one booking per slot, so it is replaced by a normal index.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $t) {
            $t->unsignedTinyInteger('slot_capacity')->default(1)->after('slot_minutes');
        });
        Schema::table('appointments', function (Blueprint $t) {
            $t->index(['doctor_id', 'date', 'time'], 'appts_doctor_date_time_idx'); // added first: the doctor_id foreign key needs an index
        });
        Schema::table('appointments', function (Blueprint $t) {
            $t->dropUnique(['doctor_id', 'date', 'time']);
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $t) {
            $t->unique(['doctor_id', 'date', 'time']);
        });
        Schema::table('appointments', function (Blueprint $t) {
            $t->dropIndex('appts_doctor_date_time_idx');
        });
        Schema::table('doctors', function (Blueprint $t) {
            $t->dropColumn('slot_capacity');
        });
    }
};
