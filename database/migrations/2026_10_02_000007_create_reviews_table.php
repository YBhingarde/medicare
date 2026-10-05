<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('appointment_id')->unique()->constrained()->cascadeOnDelete(); // one review per completed visit
            $t->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $t->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $t->unsignedTinyInteger('rating'); // 1-5
            $t->text('comment')->nullable();
            $t->json('tags')->nullable();
            $t->boolean('is_visible')->default(true); // admin can hide a review
            $t->timestamps();
            $t->index(['doctor_id', 'is_visible']);
        });

        Schema::table('appointments', function (Blueprint $t) {
            $t->boolean('review_skipped')->default(false); // patient chose "don't ask again"
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $t) { $t->dropColumn('review_skipped'); });
        Schema::dropIfExists('reviews');
    }
};
