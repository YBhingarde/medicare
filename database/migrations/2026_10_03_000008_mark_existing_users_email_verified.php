<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Email verification is new. Everybody who already has an account (patients, admins, approved doctors)
 * keeps working; doctors who are still pending / rejected must verify their email like new sign-ups.
 */
return new class extends Migration {
    public function up(): void
    {
        $waiting = DB::table('doctors')->whereIn('verification_status', ['pending', 'rejected'])->pluck('user_id');
        DB::table('users')->whereNull('email_verified_at')->whereNotIn('id', $waiting)->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // nothing to undo
    }
};
