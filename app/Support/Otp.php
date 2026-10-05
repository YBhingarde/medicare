<?php
namespace App\Support;

use App\Mail\OtpMail;
use App\Models\EmailOtp;
use Illuminate\Support\Facades\Mail;

/**
 * 6-digit email OTP used for "verify email" and "forgot password".
 *  - the code is stored only as an HMAC hash (a database leak does not reveal live codes)
 *  - valid for 10 minutes, one code per email + purpose (a new code replaces the old one)
 *  - 5 wrong guesses burn the code, a new one must be requested
 *  - a new code can be requested once every 60 seconds
 */
class Otp
{
    public const VERIFY = 'verify_email';
    public const RESET = 'reset_password';
    public const TTL_MINUTES = 10;
    public const MAX_ATTEMPTS = 5;
    public const COOLDOWN_SECONDS = 60;

    /** Real error text of the last failed send (shown to the user only when APP_DEBUG=true). */
    public static ?string $lastError = null;

    /** Make a code and email it. Returns 'sent', 'wait' (asked again too soon) or 'failed' (mail could not be sent). */
    public static function send(string $email, string $purpose, string $name = ''): string
    {
        $email = self::clean($email);
        $code = self::issue($email, $purpose);
        if ($code === null) return 'wait';
        try {
            Mail::to($email)->send(new OtpMail($code, $purpose, $name, self::TTL_MINUTES));
        } catch (\Throwable $e) {
            report($e);
            self::$lastError = $e->getMessage();
            EmailOtp::where('email', $email)->where('purpose', $purpose)->delete(); // so the user can retry at once
            return 'failed';
        }
        return 'sent';
    }

    /** Check a code. Returns 'ok', 'invalid', 'expired', 'locked' or 'none' (no code was requested). */
    public static function verify(string $email, string $purpose, string $code): string
    {
        $email = self::clean($email);
        $row = EmailOtp::where('email', $email)->where('purpose', $purpose)->first();
        if (! $row) return 'none';
        if ($row->expires_at->isPast()) { $row->delete(); return 'expired'; }
        if ($row->attempts >= self::MAX_ATTEMPTS) { $row->delete(); return 'locked'; }

        if (! hash_equals($row->code_hash, self::hash($email, $purpose, trim($code)))) {
            $row->increment('attempts');
            if ($row->attempts >= self::MAX_ATTEMPTS) { $row->delete(); return 'locked'; }
            return 'invalid';
        }
        $row->delete(); // a code works only once
        return 'ok';
    }

    /** Text shown to the user for a failed check. */
    public static function message(string $result): string
    {
        return match ($result) {
            'expired' => 'This code has expired. Please request a new one.',
            'locked' => 'Too many wrong attempts. Please request a new code.',
            'none' => 'No active code found. Please request a new one.',
            default => 'That code is not correct. Please check the email and try again.',
        };
    }

    private static function issue(string $email, string $purpose): ?string
    {
        $row = EmailOtp::where('email', $email)->where('purpose', $purpose)->first();
        if ($row && $row->last_sent_at && $row->last_sent_at->gt(now()->subSeconds(self::COOLDOWN_SECONDS))) return null;

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        EmailOtp::updateOrCreate(['email' => $email, 'purpose' => $purpose], [
            'code_hash' => self::hash($email, $purpose, $code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            'last_sent_at' => now(),
        ]);
        return $code;
    }

    private static function hash(string $email, string $purpose, string $code): string
    {
        return hash_hmac('sha256', $email . '|' . $purpose . '|' . $code, (string) config('app.key'));
    }

    private static function clean(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
