<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Structured prescription (diagnosis, medicines, advice, follow-up) that is printed as a PDF. The old text column stays as a plain-text summary. */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $t) {
            $t->json('prescription_data')->nullable()->after('prescription');
            $t->timestamp('prescription_at')->nullable()->after('prescription_data');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $t) {
            $t->dropColumn(['prescription_data', 'prescription_at']);
        });
    }
};
