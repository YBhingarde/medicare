<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $t) {
            // pending -> approved / rejected. Existing (seeded / admin-added) doctors stay 'approved'.
            $t->string('verification_status', 20)->default('approved');
            $t->string('license_council', 120)->nullable();
            $t->unsignedSmallInteger('license_year')->nullable();
            $t->string('hospital_name', 150)->nullable();
            $t->string('hospital_address')->nullable();
            $t->string('hospital_phone', 20)->nullable();
            // private files (storage/app/private/doctor-verification/...), only admin can open them
            $t->string('license_document')->nullable();
            $t->string('id_document')->nullable();
            $t->string('hospital_document')->nullable();
            $t->text('rejection_reason')->nullable();
            $t->timestamp('submitted_at')->nullable();
            $t->timestamp('verified_at')->nullable();
            $t->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $t) {
            $t->dropConstrainedForeignId('verified_by');
            $t->dropColumn(['verification_status', 'license_council', 'license_year', 'hospital_name', 'hospital_address',
                'hospital_phone', 'license_document', 'id_document', 'hospital_document', 'rejection_reason',
                'submitted_at', 'verified_at']);
        });
    }
};
