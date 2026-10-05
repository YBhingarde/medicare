<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Video link of Online appointments. app = MediCare's own room /consult/{id} (Jitsi inside the site, no setup); jitsi = plain Jitsi link. zoom = needs a Server-to-Server OAuth app; google = real Google Meet link made through the Google Calendar API (one-time setup, see MeetingLink.php). zoom / google fall back to the own room on error.
    'meetings' => [
        'driver' => env('MEETING_DRIVER', 'app'),
        'jitsi_domain' => env('JITSI_DOMAIN', 'meet.jit.si'),
        'zoom' => [
            'account_id' => env('ZOOM_ACCOUNT_ID'),
            'client_id' => env('ZOOM_CLIENT_ID'),
            'client_secret' => env('ZOOM_CLIENT_SECRET'),
        ],
        'google' => [
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'refresh_token' => env('GOOGLE_REFRESH_TOKEN'),
            'calendar_id' => env('GOOGLE_CALENDAR_ID', 'primary'),
        ],
    ],

    // SMS + WhatsApp (Twilio). E-mail reminders always work; SMS / WhatsApp only when switched on below.
    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
        'sms_from' => env('TWILIO_SMS_FROM'),
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM'),
        'whatsapp_template' => env('TWILIO_WHATSAPP_TEMPLATE_SID'),
    ],

    'reminders' => [
        'sms' => env('REMINDER_SMS', false),
        'whatsapp' => env('REMINDER_WHATSAPP', false),
        'country_code' => env('REMINDER_COUNTRY_CODE', '91'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],


    // Online payment of the appointment fee (India: UPI, cards, net banking, wallets). Leave the keys empty and the site works exactly as before
    // (fee is paid at the clinic). Keys: Razorpay Dashboard > Account & Settings > API Keys. Webhook secret: Dashboard > Webhooks.
    'razorpay' => [
        'key_id' => env('RAZORPAY_KEY_ID'),
        'key_secret' => env('RAZORPAY_KEY_SECRET'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
        'pay_window_minutes' => (int) env('PAYMENT_WINDOW_MINUTES', 15), // an online booking that is not paid within this time is released
    ],

];
