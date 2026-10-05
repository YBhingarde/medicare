<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('doctors', function (Blueprint $t) {
            $t->string('designation')->nullable();
            $t->string('license_no')->nullable();
            $t->string('gender', 10)->nullable();
            $t->string('blood_group', 5)->nullable();
            $t->date('dob')->nullable();
            $t->string('photo')->nullable();
            $t->string('address')->nullable();
            $t->string('appointment_type', 20)->default('Clinic');
            $t->unsignedSmallInteger('advance_days')->default(30);
            $t->json('languages')->nullable();
            $t->json('education')->nullable();
            $t->json('awards')->nullable();
            $t->json('certifications')->nullable();
            $t->json('availability')->nullable();
        });
    }
    public function down(): void {
        Schema::table('doctors', function (Blueprint $t) {
            $t->dropColumn(['designation','license_no','gender','blood_group','dob','photo','address','appointment_type','advance_days','languages','education','awards','certifications','availability']);
        });
    }
};
