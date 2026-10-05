<?php
namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Transaction;
use App\Support\AppointmentNotifier;
use App\Support\Razorpay;
use Illuminate\Http\Request;

/** Online payment of the appointment fee with Razorpay Checkout (UPI, cards, net banking, wallets). */
class PaymentController extends Controller
{
    /** Pay page: makes (or re-uses) the Razorpay order and opens Checkout. */
    public function show(Request $r, Appointment $appointment)
    {
        abort_unless($appointment->patient_id === $r->user()->id, 403);
        $tx = $appointment->transaction;
        if (! $tx || ! $tx->pay_online) return redirect()->route('patient.appointments');
        if ($tx->gateway_payment_id) return redirect()->route('patient.profile', ['tab' => 'transactions'])->with('ok', 'This visit is already paid.');
        if (! $tx->awaitingPayment() || ! $appointment->isOpen()) {
            return redirect()->route('patient.appointments')->withErrors(['cancel' => 'This booking can no longer be paid. Please book again.']);
        }
        if (! Razorpay::isReady()) {
            return redirect()->route('patient.appointments')->withErrors(['cancel' => 'Online payment is not available right now.']);
        }

        try {
            if (blank($tx->gateway_order_id)) {
                $order = Razorpay::createOrder((int) $tx->amount, $tx->reference, ['appointment' => (string) $appointment->id, 'patient' => (string) $tx->patient_id]);
                $tx->forceFill(['gateway_order_id' => $order['id']])->saveQuietly();
            }
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('patient.appointments')->withErrors(['cancel' => 'Could not start the payment. Please try again in a moment.']);
        }

        $appointment->load('doctor.user', 'doctor.speciality');
        return view('patient.pay', [
            'a' => $appointment, 'tx' => $tx, 'keyId' => Razorpay::keyId(), 'user' => $r->user(),
            'minutes' => $tx->payMinutesLeft(),
        ]);
    }

    /** Checkout success: the signature is verified on the server, then the payment is checked with Razorpay. */
    public function verify(Request $r, Appointment $appointment)
    {
        abort_unless($appointment->patient_id === $r->user()->id, 403);
        $d = $r->validate([
            'razorpay_payment_id' => 'required|string|max:40',
            'razorpay_order_id' => 'required|string|max:40',
            'razorpay_signature' => 'required|string|max:128',
        ]);
        $tx = $appointment->transaction;
        if (! $tx || $tx->gateway_order_id !== $d['razorpay_order_id']) abort(422, 'Order does not match this booking.');
        if (! Razorpay::validSignature($d['razorpay_order_id'], $d['razorpay_payment_id'], $d['razorpay_signature'])) {
            return redirect()->route('patient.appointments')->withErrors(['cancel' => 'Payment could not be verified. If money was deducted it will be refunded by your bank.']);
        }

        try {
            $p = Razorpay::fetchPayment($d['razorpay_payment_id']);
            if (($p['order_id'] ?? null) !== $tx->gateway_order_id || (int) ($p['amount'] ?? 0) !== (int) $tx->amount * 100) {
                abort(422, 'Payment details do not match this booking.');
            }
            if (($p['status'] ?? '') === 'authorized') $p = Razorpay::capture($d['razorpay_payment_id'], (int) $tx->amount); // account not on auto-capture
            if (($p['status'] ?? '') !== 'captured') {
                return redirect()->route('patient.appointments')->withErrors(['cancel' => 'Payment is not completed yet. If money was deducted, it will show here shortly.']);
            }
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('patient.appointments')->withErrors(['cancel' => 'We could not confirm the payment right now. If money was deducted it will show here shortly.']);
        }

        return $this->finish($appointment, $d['razorpay_payment_id'], $p['method'] ?? null);
    }

    /** Razorpay webhook (payment.captured / order.paid): the safety net when the patient closes the browser after paying. */
    public function webhook(Request $r)
    {
        if (! Razorpay::validWebhook($r->getContent(), $r->header('X-Razorpay-Signature'))) return response('invalid', 400);

        $event = (string) $r->input('event');
        if (! in_array($event, ['payment.captured', 'order.paid'])) return response('ignored', 200);

        $pay = $r->input('payload.payment.entity', []);
        $orderId = $pay['order_id'] ?? $r->input('payload.order.entity.id');
        $paymentId = $pay['id'] ?? null;
        if (! $orderId || ! $paymentId) return response('ignored', 200);

        $tx = Transaction::where('gateway_order_id', $orderId)->first();
        if (! $tx || (int) ($pay['amount'] ?? $tx->amount * 100) !== (int) $tx->amount * 100) return response('ignored', 200);

        $this->finish($tx->appointment, $paymentId, $pay['method'] ?? null, false);
        return response('ok', 200);
    }

    private function finish(Appointment $appointment, string $paymentId, ?string $method, bool $redirect = true)
    {
        $state = Transaction::markPaid($appointment->transaction->id, $paymentId, $method);
        if ($state === 'paid') {
            AppointmentNotifier::send($appointment->fresh(), 'booked', 'patient'); // the doctor hears about the request only now
        }
        if (! $redirect) return null;

        return match ($state) {
            'paid' => redirect()->route('patient.appointments')->with('ok', 'Payment received. The doctor will confirm your appointment soon.'),
            'late' => redirect()->route('patient.appointments')->withErrors(['cancel' => 'Your booking had already been released, so the payment will be refunded to your account within a few days.']),
            default => redirect()->route('patient.appointments')->with('ok', 'This visit is already paid.'),
        };
    }
}
