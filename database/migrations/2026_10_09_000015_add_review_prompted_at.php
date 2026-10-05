<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** The rating pop-up is shown ONCE per completed visit. This column remembers that it was already shown. */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $t) {
            $t->timestamp('review_prompted_at')->nullable();
        });
        // visits that were completed before this update must not suddenly pop up now
        DB::table('appointments')->where('status', 'completed')->update(['review_prompted_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $t) { $t->dropColumn('review_prompted_at'); });
    }
};
