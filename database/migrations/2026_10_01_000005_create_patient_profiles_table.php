<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('patient_profiles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->date('dob')->nullable();
            $t->string('gender', 10)->nullable();
            $t->string('blood_group', 5)->nullable();
            $t->string('address')->nullable();
            $t->string('city', 80)->nullable();
            $t->string('pincode', 10)->nullable();
            $t->string('photo')->nullable();
            $t->string('emergency_name', 100)->nullable();
            $t->string('emergency_phone', 20)->nullable();
            $t->text('allergies')->nullable();
            $t->text('medical_notes')->nullable();
            $t->foreignId('preferred_doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            // self-reported vital signs
            $t->string('bp', 15)->nullable();               // e.g. 120/80
            $t->unsignedSmallInteger('heart_rate')->nullable();
            $t->unsignedTinyInteger('spo2')->nullable();
            $t->decimal('temperature', 4, 1)->nullable();   // Celsius
            $t->unsignedSmallInteger('respiratory_rate')->nullable();
            $t->decimal('weight', 5, 1)->nullable();        // kg
            $t->timestamp('vitals_updated_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_profiles');
    }
};
