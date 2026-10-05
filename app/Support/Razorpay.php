<?php
namespace App\Support;

use Illuminate\Support\Facades\Http;

/**
 * Small Razorpay client (Orders, Payments, Refunds) over the REST API - no extra package needed.
 * Amounts are in rupees everywhere in MediCare; this class converts to paise.
 */
class Razorpay
{
    private const API = 'https://api.razorpay.com/v1';

    public static function isReady(): bool
    {
        return filled(config('services.razorpay.key_id')) && filled(config('services.razorpay.key_secret'));
    }

    public static function keyId(): string
    {
        return (string) config('services.razorpay.key_id');
    }

    private static function http()
    {
        return Http::withBasicAuth((string) config('services.razorpay.key_id'), (string) config('services.razorpay.key_secret'))
            ->acceptJson()->timeout(15);
    }

    /** Creates an order for $rupees. Returns the order array (id, amount, currency ...). Throws on a gateway error. */
    public static function createOrder(int $rupees, string $receipt, array $notes = []): array
    {
        return static::http()->post(self::API . '/orders', [
            'amount' => $rupees * 100, 'currency' => 'INR', 'receipt' => $receipt, 'notes' => $notes,
        ])->throw()->json();
    }

    public static function fetchPayment(string $paymentId): array
    {
        return static::http()->get(self::API . '/payments/' . rawurlencode($paymentId))->throw()->json();
    }

    /** Only needed when the account is not on auto-capture: an "authorized" payment is captured here. */
    public static function capture(string $paymentId, int $rupees): array
    {
        return static::http()->asForm()->post(self::API . '/payments/' . rawurlencode($paymentId) . '/capture', [
            'amount' => $rupees * 100, 'currency' => 'INR',
        ])->throw()->json();
    }

    /** Full refund of a captured payment. Returns the refund array (id ...). */
    public static function refund(string $paymentId, int $rupees, array $notes = []): array
    {
        return static::http()->post(self::API . '/payments/' . rawurlencode($paymentId) . '/refund', [
            'amount' => $rupees * 100, 'speed' => 'normal', 'notes' => $notes,
        ])->throw()->json();
    }

    /** Checkout success signature: HMAC-SHA256 of "order_id|payment_id" with the key secret. */
    public static function validSignature(string $orderId, string $paymentId, string $signature): bool
    {
        $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, (string) config('services.razorpay.key_secret'));
        return hash_equals($expected, $signature);
    }

    /** Webhook signature: HMAC-SHA256 of the raw body with the webhook secret. */
    public static function validWebhook(string $rawBody, ?string $signature): bool
    {
        $secret = (string) config('services.razorpay.webhook_secret');
        if ($secret === '' || blank($signature)) return false;
        return hash_equals(hash_hmac('sha256', $rawBody, $secret), $signature);
    }
}
