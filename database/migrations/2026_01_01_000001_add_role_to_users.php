<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $t) {
            $t->string('role')->default('patient')->after('email'); // admin, doctor, patient
            $t->string('phone')->nullable()->after('role');
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $t) { $t->dropColumn(['role', 'phone']); });
    }
};
