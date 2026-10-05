<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('specialities', function (Blueprint $t) {
            $t->id(); $t->string('name')->unique(); $t->timestamps();
        });
        Schema::create('cities', function (Blueprint $t) {
            $t->id(); $t->string('name')->unique(); $t->timestamps();
        });
        Schema::create('hospitals', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $t->string('address')->nullable(); $t->string('phone')->nullable(); $t->timestamps();
        });
        Schema::create('doctors', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('speciality_id')->constrained();
            $t->foreignId('city_id')->constrained();
            $t->foreignId('hospital_id')->nullable()->constrained()->nullOnDelete();
            $t->string('qualification')->nullable();
            $t->unsignedTinyInteger('experience_years')->default(0);
            $t->unsignedInteger('fees')->default(0);
            $t->text('about')->nullable();
            $t->string('slot_start')->default('10:00');
            $t->string('slot_end')->default('17:00');
            $t->unsignedTinyInteger('slot_minutes')->default(20);
            $t->boolean('approved')->default(false);
            $t->timestamps();
        });
        Schema::create('appointments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $t->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $t->date('date');
            $t->string('time'); // HH:MM
            $t->string('reason')->nullable();
            $t->string('status')->default('pending'); // pending, confirmed, completed, cancelled
            $t->text('prescription')->nullable();
            $t->timestamps();
            $t->unique(['doctor_id', 'date', 'time']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('appointments'); Schema::dropIfExists('doctors');
        Schema::dropIfExists('hospitals'); Schema::dropIfExists('cities'); Schema::dropIfExists('specialities');
    }
};