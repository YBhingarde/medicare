<?php
namespace App\Jobs;

use App\Models\Transaction;
use App\Support\Razorpay;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\RequestException;

/**
 * Gives the money back (full refund) when an online-paid visit is cancelled or expires.
 * Runs on the queue worker and retries by itself, so a short gateway problem never loses a refund.
 */
class RefundPayment implements ShouldQueue
{
    use Queueable;

    public int $tries = 6;

    public function __construct(public int $transactionId) {}

    public function backoff(): array
    {
        return [60, 300, 900, 3600, 10800];
    }

    public function handle(): void
    {
        $tx = Transaction::find($this->transactionId);
        if (! $tx || blank($tx->gateway_payment_id) || filled($tx->refund_id) || $tx->status === 'refunded') return;

        try {
            $refund = Razorpay::refund($tx->gateway_payment_id, (int) $tx->amount, ['transaction' => $tx->reference]);
        } catch (RequestException $e) {
            $msg = (string) ($e->response?->json('error.description') ?? $e->getMessage());
            // already refunded (for example from the Razorpay dashboard): treat as done
            if (str_contains(strtolower($msg), 'fully refunded')) {
                $tx->forceFill(['status' => 'refunded', 'refund_error' => null])->save();
                return;
            }
            $tx->forceFill(['refund_error' => mb_substr($msg, 0, 250)])->save();
            throw $e; // the queue retries with the back-off above
        }

        $tx->forceFill(['status' => 'refunded', 'refund_id' => $refund['id'] ?? null, 'refund_error' => null])->save();
    }

    /** All retries used up: the reason stays on the transaction so the admin can refund it from the Razorpay dashboard. */
    public function failed(\Throwable $e): void
    {
        Transaction::whereKey($this->transactionId)->update(['refund_error' => mb_substr('Refund failed: ' . $e->getMessage(), 0, 250)]);
    }
}
