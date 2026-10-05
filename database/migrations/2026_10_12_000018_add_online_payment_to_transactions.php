<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Online payment (Razorpay): the gateway ids live on the patient's transaction row.
 *  pay_online         : the patient chose to pay online (the visit waits for the payment)
 *  gateway_order_id   : Razorpay order made for this transaction
 *  gateway_payment_id : filled once the payment is verified (this is what makes the transaction "paid online")
 *  refund_id          : Razorpay refund made when a paid visit is cancelled / expires
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $t) {
            $t->boolean('pay_online')->default(false)->after('status');
            $t->string('gateway_order_id', 40)->nullable()->index()->after('pay_online');
            $t->string('gateway_payment_id', 40)->nullable()->index()->after('gateway_order_id');
            $t->string('refund_id', 40)->nullable()->after('gateway_payment_id');
            $t->string('refund_error', 255)->nullable()->after('refund_id');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $t) {
            $t->dropColumn(['pay_online', 'gateway_order_id', 'gateway_payment_id', 'refund_id', 'refund_error']);
        });
    }
};
