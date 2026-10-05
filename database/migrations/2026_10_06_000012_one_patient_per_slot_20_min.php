<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** One patient per slot (then the slot shows Full) and 20-minute slots, for new and existing doctors. */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $t) {
            $t->unsignedTinyInteger('slot_capacity')->default(1)->change();
            $t->unsignedTinyInteger('slot_minutes')->default(20)->change();
        });
        DB::table('doctors')->update(['slot_capacity' => 1, 'slot_minutes' => 20]);
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $t) {
            $t->unsignedTinyInteger('slot_capacity')->default(3)->change();
            $t->unsignedTinyInteger('slot_minutes')->default(30)->change();
        });
    }
};
