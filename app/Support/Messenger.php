<?php
namespace App\Support;

use Illuminate\Support\Facades\Http;

/** SMS and WhatsApp through Twilio (plain REST call, no extra package). Credentials: config/services.php -> twilio. */
class Messenger
{
    /** "98765 43210", "09876543210", "+919876543210" -> "+919876543210". Returns null when it cannot be a valid number. */
    public static function e164(?string $phone): ?string
    {
        $raw = trim((string) $phone);
        $digits = preg_replace('/\D+/', '', $raw);
        if ($digits === '') return null;
        if (str_starts_with($raw, '+')) return strlen($digits) >= 11 ? '+' . $digits : null;
        if (str_starts_with($digits, '00')) return '+' . substr($digits, 2);

        $cc = (string) config('services.reminders.country_code', '91');
        $digits = ltrim($digits, '0');
        if (strlen($digits) === 10) return '+' . $cc . $digits;
        if (str_starts_with($digits, $cc) && strlen($digits) === strlen($cc) + 10) return '+' . $digits;
        return null;
    }

    public static function smsReady(): bool
    {
        return (bool) config('services.reminders.sms') && static::credentials() && filled(config('services.twilio.sms_from'));
    }

    public static function whatsappReady(): bool
    {
        return (bool) config('services.reminders.whatsapp') && static::credentials() && filled(config('services.twilio.whatsapp_from'));
    }

    public static function sms(string $to, string $body): void
    {
        static::send(['From' => config('services.twilio.sms_from'), 'To' => $to, 'Body' => $body]);
    }

    /**
     * WhatsApp only lets a business start a chat with an approved template. With TWILIO_WHATSAPP_TEMPLATE_SID set,
     * $vars ({1}, {2}, ...) fill that template; without it the plain text is sent (works for the Twilio sandbox only).
     */
    public static function whatsapp(string $to, string $body, array $vars = []): void
    {
        $data = ['From' => 'whatsapp:' . config('services.twilio.whatsapp_from'), 'To' => 'whatsapp:' . $to];
        if ($sid = config('services.twilio.whatsapp_template')) {
            $data['ContentSid'] = $sid;
            $data['ContentVariables'] = json_encode($vars, JSON_UNESCAPED_UNICODE);
        } else {
            $data['Body'] = $body;
        }
        static::send($data);
    }

    protected static function credentials(): bool
    {
        return filled(config('services.twilio.sid')) && filled(config('services.twilio.token'));
    }

    protected static function send(array $data): void
    {
        $t = config('services.twilio');
        Http::asForm()->withBasicAuth($t['sid'], $t['token'])->timeout(10)
            ->post('https://api.twilio.com/2010-04-01/Accounts/' . $t['sid'] . '/Messages.json', $data)
            ->throw();
    }
}
