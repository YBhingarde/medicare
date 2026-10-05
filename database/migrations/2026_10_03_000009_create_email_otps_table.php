<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** One row per (email, purpose): the hashed 6-digit code, how often it was guessed wrong and when it expires. */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_otps', function (Blueprint $t) {
            $t->id();
            $t->string('email');
            $t->string('purpose', 30);          // verify_email | reset_password
            $t->string('code_hash', 64);        // never store the code itself
            $t->unsignedTinyInteger('attempts')->default(0);
            $t->timestamp('expires_at');
            $t->timestamp('last_sent_at')->nullable();
            $t->timestamps();
            $t->unique(['email', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_otps');
    }
};
