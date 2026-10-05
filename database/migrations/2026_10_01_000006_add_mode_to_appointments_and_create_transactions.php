<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $t) {
            $t->string('mode', 20)->default('In-person'); // In-person or Online
        });

        Schema::create('transactions', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 20)->unique();
            $t->foreignId('appointment_id')->unique()->constrained()->cascadeOnDelete();
            $t->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('amount')->default(0);
            $t->string('method', 30)->default('Pay after visit');
            $t->string('status', 20)->default('pending'); // pending, paid, refunded, cancelled
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();
        });

        // Give every existing appointment a transaction row, so old bookings show up in the new Transactions tab.
        DB::table('appointments')->join('doctors', 'doctors.id', '=', 'appointments.doctor_id')
            ->select('appointments.id', 'appointments.patient_id', 'appointments.doctor_id', 'appointments.status',
                'appointments.date', 'appointments.time', 'doctors.fees')
            ->orderBy('appointments.id')
            ->chunk(500, function ($rows) {
                $now = now();
                DB::table('transactions')->insert($rows->map(fn ($a) => [
                    'reference' => 'TXN' . str_pad((string) $a->id, 6, '0', STR_PAD_LEFT),
                    'appointment_id' => $a->id, 'patient_id' => $a->patient_id, 'doctor_id' => $a->doctor_id,
                    'amount' => (int) $a->fees, 'method' => 'Pay after visit',
                    'status' => match ($a->status) { 'completed' => 'paid', 'cancelled' => 'cancelled', default => 'pending' },
                    'paid_at' => $a->status === 'completed' ? substr((string) $a->date, 0, 10) . ' ' . substr((string) $a->time, 0, 5) . ':00' : null,
                    'created_at' => $now, 'updated_at' => $now,
                ])->all());
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
        Schema::table('appointments', function (Blueprint $t) { $t->dropColumn('mode'); });
    }
};
