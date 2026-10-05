<?php
namespace Tests\Feature;

use App\Models\{Appointment, Doctor, Transaction, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OnlinePaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $patient;
    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-10 09:00:00'));
        config(['services.razorpay.key_id' => 'rzp_test_x', 'services.razorpay.key_secret' => 'secret', 'services.razorpay.webhook_secret' => 'whsec']);
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $this->doctor = Doctor::with('user')->first();
        $this->doctor->update(['fees' => 500]);
        $this->patient = User::create(['name' => 'Pat', 'email' => 'p@t.test', 'password' => 'x', 'role' => 'patient', 'email_verified_at' => now()]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function awaiting(): Appointment
    {
        $a = Appointment::create(['doctor_id' => $this->doctor->id, 'patient_id' => $this->patient->id, 'date' => '2026-10-12',
            'time' => '10:00', 'status' => 'pending', 'mode' => 'Online']);
        Transaction::where('appointment_id', $a->id)->update(['pay_online' => true, 'gateway_order_id' => 'order_1', 'method' => 'Online (Razorpay)']);
        return $a->fresh();
    }

    private function sig(string $order, string $pay): string
    {
        return hash_hmac('sha256', $order . '|' . $pay, 'secret');
    }

    public function test_unpaid_online_booking_cannot_be_confirmed(): void
    {
        $a = $this->awaiting();
        $this->assertTrue($a->awaitingPayment());
        $this->assertSame('Awaiting payment', $a->statusLabel());
        $this->assertNotNull($a->changeStatus('confirmed', 'doctor'));
        $this->assertSame('pending', $a->fresh()->status);
    }

    public function test_verified_payment_marks_paid_and_allows_confirm(): void
    {
        Http::fake(['api.razorpay.com/v1/payments/pay_1' => Http::response(['id' => 'pay_1', 'order_id' => 'order_1', 'amount' => 50000, 'status' => 'captured', 'method' => 'upi'])]);
        $a = $this->awaiting();
        $this->actingAs($this->patient)->post(route('patient.pay.verify', $a), [
            'razorpay_payment_id' => 'pay_1', 'razorpay_order_id' => 'order_1', 'razorpay_signature' => $this->sig('order_1', 'pay_1'),
        ])->assertRedirect(route('patient.appointments'));

        $tx = $a->transaction()->first();
        $this->assertSame('paid', $tx->status);
        $this->assertSame('pay_1', $tx->gateway_payment_id);
        $this->assertSame('Online - UPI', $tx->method);
        $this->assertNull($a->fresh()->changeStatus('confirmed', 'doctor'));
        $this->assertSame('paid', $a->transaction()->first()->status); // stays paid after the status change
    }

    public function test_wrong_signature_is_rejected(): void
    {
        $a = $this->awaiting();
        $this->actingAs($this->patient)->post(route('patient.pay.verify', $a), [
            'razorpay_payment_id' => 'pay_1', 'razorpay_order_id' => 'order_1', 'razorpay_signature' => 'bad',
        ])->assertRedirect(route('patient.appointments'));
        $this->assertSame('pending', $a->transaction()->first()->status);
        $this->assertNull($a->transaction()->first()->gateway_payment_id);
    }

    public function test_amount_mismatch_is_rejected(): void
    {
        Http::fake(['api.razorpay.com/*' => Http::response(['id' => 'pay_1', 'order_id' => 'order_1', 'amount' => 100, 'status' => 'captured'])]);
        $a = $this->awaiting();
        $this->actingAs($this->patient)->post(route('patient.pay.verify', $a), [
            'razorpay_payment_id' => 'pay_1', 'razorpay_order_id' => 'order_1', 'razorpay_signature' => $this->sig('order_1', 'pay_1'),
        ])->assertStatus(422);
        $this->assertNull($a->transaction()->first()->gateway_payment_id);
    }

    public function test_webhook_marks_paid_once_and_rejects_bad_signature(): void
    {
        $a = $this->awaiting();
        $body = json_encode(['event' => 'payment.captured', 'payload' => ['payment' => ['entity' => ['id' => 'pay_9', 'order_id' => 'order_1', 'amount' => 50000, 'method' => 'card']]]]);
        $this->call('POST', '/razorpay/webhook', [], [], [], ['HTTP_X-Razorpay-Signature' => 'nope', 'CONTENT_TYPE' => 'application/json'], $body)->assertStatus(400);
        $good = hash_hmac('sha256', $body, 'whsec');
        $this->call('POST', '/razorpay/webhook', [], [], [], ['HTTP_X-Razorpay-Signature' => $good, 'CONTENT_TYPE' => 'application/json'], $body)->assertOk();
        $this->call('POST', '/razorpay/webhook', [], [], [], ['HTTP_X-Razorpay-Signature' => $good, 'CONTENT_TYPE' => 'application/json'], $body)->assertOk();
        $this->assertSame('pay_9', $a->transaction()->first()->gateway_payment_id);
        $this->assertSame('paid', $a->transaction()->first()->status);
    }

    public function test_cancelling_a_paid_visit_refunds_the_money(): void
    {
        Http::fake(['api.razorpay.com/v1/payments/pay_1/refund' => Http::response(['id' => 'rfnd_1'])]);
        $a = $this->awaiting();
        Transaction::markPaid($a->transaction()->first()->id, 'pay_1', 'upi');
        $this->assertNull($a->fresh()->changeStatus('cancelled', 'patient')); // queue is sync in tests
        $tx = $a->transaction()->first();
        $this->assertSame('refunded', $tx->status);
        $this->assertSame('rfnd_1', $tx->refund_id);
        Http::assertSentCount(1);
    }

    public function test_payment_after_the_booking_was_released_is_refunded(): void
    {
        Http::fake(['api.razorpay.com/v1/payments/pay_2/refund' => Http::response(['id' => 'rfnd_2'])]);
        $a = $this->awaiting();
        $a->update(['status' => 'cancelled', 'cancelled_by' => 'system']);
        $this->assertSame('late', Transaction::markPaid($a->transaction()->first()->id, 'pay_2', 'upi'));
        $this->assertSame('refunded', $a->transaction()->first()->status);
    }

    public function test_unpaid_online_booking_is_released_after_the_window(): void
    {
        $a = $this->awaiting();
        Carbon::setTestNow(Carbon::parse('2026-10-10 09:16:00'));
        Appointment::expireStale();
        $this->assertSame('cancelled', $a->fresh()->status);
        $this->assertSame('cancelled', $a->transaction()->first()->status);
    }

    public function test_booking_form_starts_payment_when_gateway_is_set(): void
    {
        $this->doctor->update(['appointment_type' => 'Online', 'approved' => true]);
        $date = '2026-10-12';
        $time = $this->doctor->slotsFor($date)[0] ?? null;
        $this->assertNotNull($time, 'doctor has a slot');
        $res = $this->actingAs($this->patient)->post(route('book', $this->doctor), ['date' => $date, 'time' => $time, 'payment' => 'clinic']);
        $a = Appointment::where('patient_id', $this->patient->id)->firstOrFail();
        $res->assertRedirect(route('patient.pay', $a)); // Online visits are always prepaid
        $this->assertTrue($a->transaction()->first()->pay_online);
    }

    public function test_clinic_payment_still_works_without_keys(): void
    {
        config(['services.razorpay.key_id' => null, 'services.razorpay.key_secret' => null]);
        $this->doctor->update(['appointment_type' => 'In-person', 'approved' => true]);
        $time = $this->doctor->slotsFor('2026-10-12')[0];
        $this->actingAs($this->patient)->post(route('book', $this->doctor), ['date' => '2026-10-12', 'time' => $time])->assertRedirect(route('patient.appointments'));
        $a = Appointment::where('patient_id', $this->patient->id)->firstOrFail();
        $this->assertFalse($a->transaction()->first()->pay_online);
        $this->assertFalse($a->awaitingPayment());
    }

    public function test_pay_page_opens_checkout_and_other_patients_are_blocked(): void
    {
        Http::fake(['api.razorpay.com/v1/orders' => Http::response(['id' => 'order_77', 'amount' => 50000])]);
        $a = $this->awaiting();
        Transaction::where('appointment_id', $a->id)->update(['gateway_order_id' => null]);
        $this->actingAs($this->patient)->get(route('patient.pay', $a))->assertOk()
            ->assertSee('checkout.razorpay.com')->assertSee('order_77')->assertSee('500');
        $other = User::create(['name' => 'Other', 'email' => 'o@t.test', 'password' => 'x', 'role' => 'patient', 'email_verified_at' => now()]);
        $this->actingAs($other)->get(route('patient.pay', $a))->assertForbidden();
    }
}
