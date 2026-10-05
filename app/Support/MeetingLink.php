<?php
namespace App\Support;

use App\Models\Appointment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Creates the video-call link of an Online appointment.
 *   MEETING_DRIVER=app (default)   : MediCare's own room, /consult/{appointment} (Jitsi inside an iframe, login required).
 *   MEETING_DRIVER=jitsi           : plain meet.jit.si link that opens on the Jitsi site.
 *   MEETING_DRIVER=zoom            : creates a real Zoom meeting (Server-to-Server OAuth app); falls back to the own room on any error.
 *   MEETING_DRIVER=google          : creates a real Google Meet link (Google Calendar API, one clinic Google account + refresh token); falls back to the own room on any error.
 * Independent of the driver, a doctor can press "Create Google Meet link" on an appointment when the GOOGLE_* keys are filled (see isGoogleReady()).
 */
class MeetingLink
{
    public static function generate(Appointment $a): string
    {
        $driver = config('services.meetings.driver');
        if ($driver === 'jitsi') return static::jitsi($a);
        if ($driver === 'zoom') {
            try {
                if ($url = static::zoom($a)) return $url;
            } catch (\Throwable $e) {
                report($e); // never block confirming a visit because Zoom is down
            }
        }
        if ($driver === 'google') {
            try {
                if ($url = static::google($a)) return $url;
            } catch (\Throwable $e) {
                report($e); // never block confirming a visit because Google is down
            }
        }
        return route('consult.show', $a);
    }

    /** True when the three Google keys are in .env, so a Meet link can be created. */
    public static function isGoogleReady(): bool
    {
        $g = (array) config('services.meetings.google');
        return filled($g['client_id'] ?? null) && filled($g['client_secret'] ?? null) && filled($g['refresh_token'] ?? null);
    }

    /**
     * Creates a Google Calendar event with a Meet room and returns the meet.google.com link (null when the keys are missing).
     * Throws on a Google error - generate() catches it, the doctor button shows the message.
     */
    public static function google(Appointment $a): ?string
    {
        if (! static::isGoogleReady()) return null;
        $g = (array) config('services.meetings.google');

        $token = Cache::remember('google:access-token', 3000, fn () => Http::asForm()->timeout(8)
            ->post('https://oauth2.googleapis.com/token', [
                'client_id' => $g['client_id'], 'client_secret' => $g['client_secret'],
                'refresh_token' => $g['refresh_token'], 'grant_type' => 'refresh_token',
            ])->throw()->json('access_token'));

        $a->loadMissing('doctor.user', 'patient');
        $start = $a->startsAt();
        $end = $start->copy()->addMinutes(max(10, (int) ($a->doctor?->slot_minutes ?: 20)));
        $tz = config('app.timezone');
        $attendees = collect([$a->doctor?->user?->email, $a->patient?->email])->filter()->unique()->map(fn ($e) => ['email' => $e])->values()->all();

        $res = Http::withToken($token)->timeout(10)
            ->post('https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode($g['calendar_id'] ?: 'primary') . '/events?conferenceDataVersion=1&sendUpdates=none', [
                'summary' => 'MediCare consultation - ' . ($a->doctor?->user?->name ?? 'Doctor'),
                'description' => 'Online appointment #' . $a->id . ' booked on MediCare.',
                'start' => ['dateTime' => $start->format('Y-m-d\TH:i:s'), 'timeZone' => $tz],
                'end' => ['dateTime' => $end->format('Y-m-d\TH:i:s'), 'timeZone' => $tz],
                'attendees' => $attendees,
                'conferenceData' => ['createRequest' => [
                    'requestId' => 'medicare-' . $a->id . '-' . Str::lower(Str::random(8)),
                    'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                ]],
            ]);
        if ($res->status() === 401) Cache::forget('google:access-token'); // expired token: next time a fresh one
        return $res->throw()->json('hangoutLink');
    }

    public static function jitsi(Appointment $a): string
    {
        $domain = trim((string) config('services.meetings.jitsi_domain', 'meet.jit.si'), '/');
        return 'https://' . $domain . '/MediCare-' . $a->id . '-' . Str::lower(Str::random(12)); // random part = unguessable room
    }

    /** Makes sure an Online appointment has a link (also fixes old confirmed visits that have none). Safe to call from parallel jobs. */
    public static function ensure(Appointment $a): void
    {
        if ($a->mode !== 'Online' || filled($a->meeting_link)) return;

        $link = DB::transaction(function () use ($a) {
            $row = Appointment::whereKey($a->id)->lockForUpdate()->first();
            if (! $row) return null;
            if (blank($row->meeting_link)) {
                $row->setRelation('doctor', $a->doctor);
                $row->forceFill(['meeting_link' => static::generate($row)])->saveQuietly();
            }
            return $row->meeting_link;
        });
        if ($link) $a->meeting_link = $link;
    }

    protected static function zoom(Appointment $a): ?string
    {
        $z = (array) config('services.meetings.zoom');
        if (blank($z['account_id'] ?? null) || blank($z['client_id'] ?? null) || blank($z['client_secret'] ?? null)) return null;

        $token = Cache::remember('zoom:access-token', 3000, fn () => Http::asForm()
            ->withBasicAuth($z['client_id'], $z['client_secret'])->timeout(8)
            ->post('https://zoom.us/oauth/token?grant_type=account_credentials&account_id=' . urlencode($z['account_id']), [])
            ->throw()->json('access_token'));

        $res = Http::withToken($token)->timeout(8)->post('https://api.zoom.us/v2/users/me/meetings', [
            'topic' => 'MediCare consultation - ' . ($a->doctor?->user?->name ?? 'Doctor'),
            'type' => 2, // scheduled meeting
            'start_time' => $a->startsAt()->format('Y-m-d\TH:i:s'),
            'timezone' => config('app.timezone'),
            'duration' => max(10, (int) ($a->doctor?->slot_minutes ?: 20)),
            'settings' => ['join_before_host' => true, 'waiting_room' => false],
        ]);
        if ($res->status() === 401) Cache::forget('zoom:access-token'); // expired token: next time a fresh one
        return $res->throw()->json('join_url');
    }
}
