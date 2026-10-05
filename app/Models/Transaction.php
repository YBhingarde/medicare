<?php
namespace App\Models;

use App\Jobs\RefundPayment;
use App\Support\Razorpay;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Transaction extends Model
{
    protected $guarded = [];
    protected $casts = ['paid_at' => 'datetime', 'pay_online' => 'boolean'];

    /** status -> existing dashboard pill class */
    public const PILL = ['pending' => 'status-pending', 'paid' => 'status-completed', 'refunded' => 'status-confirmed', 'cancelled' => 'status-cancelled'];

    public function appointment() { return $this->belongsTo(Appointment::class); }
    public function doctor() { return $this->belongsTo(Doctor::class); }
    public function patient() { return $this->belongsTo(User::class, 'patient_id'); }

    /** The patient chose to pay online and has not paid yet. */
    public function awaitingPayment(): bool
    {
        return $this->pay_online && blank($this->gateway_payment_id) && $this->status === 'pending';
    }

    /** Minutes the patient still has to pay (0 when the window is over). */
    public function payMinutesLeft(): int
    {
        $end = $this->created_at->copy()->addMinutes((int) config('services.razorpay.pay_window_minutes', 15));
        return max(0, (int) ceil(now()->diffInSeconds($end, false) / 60));
    }

    /**
     * Records a verified online payment. Safe to call twice (browser return + webhook): only the first call does anything.
     * Returns 'paid' (visit stays booked), 'late' (the visit was cancelled meanwhile: the money is refunded) or 'already'.
     */
    public static function markPaid(int $transactionId, string $paymentId, ?string $method = null): string
    {
        $result = DB::transaction(function () use ($transactionId, $paymentId, $method) {
            $tx = static::whereKey($transactionId)->lockForUpdate()->firstOrFail();
            if (filled($tx->gateway_payment_id)) return ['already', $tx];
            $tx->forceFill([
                'gateway_payment_id' => $paymentId, 'status' => 'paid', 'paid_at' => now(),
                'method' => 'Online' . ($method ? ' - ' . strtoupper($method) : ' (Razorpay)'),
            ])->save();
            $visit = $tx->appointment()->value('status');
            return [in_array($visit, Appointment::OPEN) ? 'paid' : 'late', $tx];
        });

        [$state, $tx] = $result;
        if ($state === 'late') RefundPayment::dispatch($tx->id)->afterCommit();
        return $state;
    }
}
