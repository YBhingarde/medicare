<?php
namespace App\Models;

use App\Jobs\RefundPayment;
use App\Jobs\SendAppointmentReminder;
use App\Support\AppointmentNotifier;
use App\Support\MeetingLink;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Appointment extends Model
{
    /** Patients can cancel / reschedule only up to this many hours before the visit. */
    public const CANCEL_CUTOFF_HOURS = 2;

    /** The video room opens this many minutes before the visit starts. */
    public const CONSULT_EARLY_MINUTES = 10;

    /** Statuses of a visit that is still going to happen (or is happening now). */
    public const OPEN = ['pending', 'confirmed', 'in_progress'];

    /** Allowed status changes. completed, cancelled and expired are final. in_progress = the patient is in the video room. */
    public const TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['in_progress', 'completed', 'cancelled'],
        'in_progress' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
        'expired' => [],
    ];

    protected $guarded = [];
    protected $casts = ['date' => 'date', 'prescription_data' => 'array', 'prescription_at' => 'datetime',
        'reminder_24h_sent_at' => 'datetime', 'reminder_2h_sent_at' => 'datetime',
        'consult_started_at' => 'datetime', 'consult_joined_at' => 'datetime'];

    public function doctor() { return $this->belongsTo(Doctor::class); }
    public function patient() { return $this->belongsTo(User::class, 'patient_id'); }
    public function transaction() { return $this->hasOne(Transaction::class); }
    public function review() { return $this->hasOne(Review::class); }

    public function hasPrescription(): bool
    {
        return filled($this->prescription_data) || filled($this->prescription);
    }

    /** Prescription number printed on the PDF, e.g. RX000042. */
    public function rxNumber(): string
    {
        return 'RX' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    /** Fee of this visit: the amount frozen in the transaction at booking time (never today's fee). */
    public function getFeeAttribute(): int
    {
        return (int) ($this->transaction?->amount ?? $this->doctor?->fees ?? 0);
    }

    /** Online booking whose fee is not paid yet: the slot is held, but the doctor cannot confirm it. */
    public function awaitingPayment(): bool
    {
        return $this->status === 'pending' && (bool) $this->transaction?->awaitingPayment();
    }

    /** Exact date + time the visit starts. */
    public function startsAt(): Carbon
    {
        return Carbon::parse($this->date->toDateString() . ' ' . substr((string) $this->time, 0, 5));
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN);
    }

    /** "in_progress" is shown as "In progress"; the other statuses just start with a capital. */
    public function statusLabel(): string
    {
        if ($this->awaitingPayment()) return 'Awaiting payment';
        return $this->status === 'in_progress' ? 'In progress' : ucfirst((string) $this->status);
    }

    /* ---------- Video room (/consult/{appointment}) ---------- */

    /** True when the visit is held in MediCare's own room (no link, a generated link, or an old auto-made Jitsi link). */
    public function usesAppRoom(): bool
    {
        if ($this->mode !== 'Online') return false;
        $l = (string) $this->meeting_link;
        return $l === '' || str_starts_with($l, url('/consult/')) || str_contains($l, '/MediCare-');
    }

    /** Where the Join button leads: our own room, or the link the doctor pasted. */
    public function joinUrl(): ?string
    {
        if ($this->mode !== 'Online') return null;
        return $this->usesAppRoom() ? route('consult.show', $this) : $this->meeting_link;
    }

    public function consultOpensAt(): Carbon
    {
        return $this->startsAt()->subMinutes(self::CONSULT_EARLY_MINUTES);
    }

    public function consultEndsAt(): Carbon
    {
        return $this->startsAt()->addMinutes(max(5, (int) ($this->doctor?->slot_minutes ?: 20)));
    }

    /**
     * unconfirmed : the doctor has not confirmed yet
     * early       : confirmed, but the room is not open yet ("Not started yet")
     * waiting     : room open, the doctor has not pressed "Start consultation"
     * live        : the consultation is running (video is shown)
     * ended       : visit over, completed or cancelled ("Visit ended")
     * A visit that is already "In progress" stays live after its slot ended, until the doctor completes it.
     */
    public function consultPhase(): string
    {
        return match (true) {
            in_array($this->status, ['completed', 'cancelled', 'expired']) => 'ended',
            $this->status === 'pending' => 'unconfirmed',
            $this->status === 'in_progress' => 'live',
            now()->lt($this->consultOpensAt()) => 'early',
            now()->gte($this->consultEndsAt()) => 'ended',
            filled($this->consult_started_at) => 'live',
            default => 'waiting',
        };
    }

    /** Patient entered the room after the doctor started: confirmed -> in_progress (only once). */
    public function markJoined(): void
    {
        if ($this->status !== 'confirmed' || blank($this->consult_started_at)) return;
        $changed = static::whereKey($this->id)->where('status', 'confirmed')
            ->update(['status' => 'in_progress', 'consult_joined_at' => now()]);
        if ($changed) $this->forceFill(['status' => 'in_progress', 'consult_joined_at' => now()])->syncOriginal();
    }

    /** Address of the Jitsi room (the room name cannot be guessed: it is derived from the app key). */
    public function jitsiRoom(): string
    {
        return 'MediCare-' . $this->id . '-' . substr(hash_hmac('sha256', 'consult-' . $this->id, (string) config('app.key')), 0, 16);
    }

    public function jitsiEmbedUrl(string $displayName): string
    {
        $domain = trim((string) config('services.meetings.jitsi_domain', 'meet.jit.si'), '/');
        return 'https://' . $domain . '/' . $this->jitsiRoom()
            . '#userInfo.displayName=' . rawurlencode(json_encode($displayName, JSON_UNESCAPED_UNICODE))
            . '&config.prejoinPageEnabled=false&config.disableDeepLinking=true';
    }

    /** Patient may cancel / reschedule only while open and at least CANCEL_CUTOFF_HOURS before the visit. */
    public function patientCanChange(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']) && now()->addHours(self::CANCEL_CUTOFF_HOURS)->lte($this->startsAt());
    }

    /** "Completed" is possible only on or after the appointment date. */
    public function canComplete(): bool
    {
        return in_array($this->status, ['confirmed', 'in_progress']) && $this->date->toDateString() <= now()->toDateString();
    }

    /**
     * The one place where a status changes (doctor, admin, patient).
     * Returns an error message, or null when the change was made (and the e-mails were sent).
     */
    public function changeStatus(string $to, string $by): ?string
    {
        if ($to === $this->status) return null;
        if (! in_array($to, self::TRANSITIONS[$this->status] ?? [])) {
            return 'A ' . $this->status . ' appointment cannot be changed to ' . $to . '.';
        }
        if ($to === 'confirmed' && $this->awaitingPayment()) {
            return 'The patient has chosen to pay online and has not paid yet. You can confirm this visit after the payment.';
        }
        if ($to === 'completed' && $this->date->toDateString() > now()->toDateString()) {
            return 'An appointment can be marked completed only on or after its date (' . $this->date->format('d M Y') . ').';
        }
        $data = ['status' => $to];
        if ($to === 'cancelled') $data['cancelled_by'] = $by;
        if ($to === 'confirmed') {
            // Online visit: the video-call link is made here, so the "confirmed" e-mail already contains it
            if ($this->mode === 'Online' && blank($this->meeting_link)) $data['meeting_link'] = MeetingLink::generate($this);
            // no reminder for a window the confirmation itself already falls into
            $minutes = now()->diffInMinutes($this->startsAt(), false);
            if ($minutes <= 24 * 60) $data['reminder_24h_sent_at'] = now();
            if ($minutes <= 2 * 60) $data['reminder_2h_sent_at'] = now();
        }
        $this->update($data);
        AppointmentNotifier::send($this, $to, $by);
        return null;
    }

    /** Pending requests whose slot is already over become "expired" (no-show). Returns how many. */
    public static function expireStale(): int
    {
        $n = 0;
        // online bookings that were not paid in time give the slot back
        Transaction::with('appointment')->where('pay_online', true)->whereNull('gateway_payment_id')->where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes((int) config('services.razorpay.pay_window_minutes', 15)))->get()
            ->each(function (Transaction $t) use (&$n) {
                if ($t->appointment?->status === 'pending') {
                    $t->appointment->update(['status' => 'cancelled', 'cancelled_by' => 'system']);
                    $n++;
                }
            });
        static::with('doctor')->where('status', 'pending')->whereDate('date', '<=', now()->toDateString())->get()
            ->each(function (Appointment $a) use (&$n) {
                if ($a->startsAt()->addMinutes(max(5, (int) $a->doctor?->slot_minutes))->isPast()) {
                    $a->update(['status' => 'expired']); // saved hook closes the transaction too
                    $n++;
                }
            });
        return $n;
    }

    /**
     * Queues the 24-hour and the 2-hour reminders of confirmed visits that are due. Run every minute by the scheduler.
     * Each reminder is "claimed" in the database first, so two overlapping runs can never queue it twice.
     * Returns how many reminders were queued.
     */
    public static function dispatchDueReminders(): int
    {
        $n = 0;
        $now = now();
        $rows = static::where('status', 'confirmed')
            ->whereBetween('date', [$now->toDateString(), $now->copy()->addDay()->toDateString()])
            ->where(fn ($q) => $q->whereNull('reminder_24h_sent_at')->orWhereNull('reminder_2h_sent_at'))
            ->get();

        foreach ($rows as $a) {
            $left = $now->diffInMinutes($a->startsAt(), false); // minutes until the visit
            if ($left <= 0 || $left > 24 * 60) continue;

            $label = $left <= 2 * 60 ? '2h' : '24h';
            // inside the last 2 hours the 24h reminder would be stale: drop it silently, the 2h one is enough
            if ($label === '2h') static::whereKey($a->id)->whereNull('reminder_24h_sent_at')->update(['reminder_24h_sent_at' => $now]);

            $col = 'reminder_' . $label . '_sent_at';
            if (static::whereKey($a->id)->whereNull($col)->update([$col => $now]) === 0) continue; // already claimed
            foreach (SendAppointmentReminder::channels() as $channel) {
                SendAppointmentReminder::dispatch($a->id, $label, $channel);
            }
            $n++;
        }
        return $n;
    }

    /** Same as expireStale() but at most once a minute, so it is safe to call on page requests. */
    public static function expireStaleThrottled(): void
    {
        try {
            if (Cache::add('appointments:expire-lock', 1, 60)) static::expireStale();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Keeps the patient's Transactions tab in step with the appointment, wherever the status is changed from. */
    protected static function booted(): void
    {
        // a visit that is moved to another day / time gets its reminders again
        static::saving(function (Appointment $a) {
            if ($a->exists && ($a->isDirty('date') || $a->isDirty('time')) && ! $a->isDirty(['reminder_24h_sent_at', 'reminder_2h_sent_at'])) {
                $a->reminder_24h_sent_at = null;
                $a->reminder_2h_sent_at = null;
            }
        });

        static::saved(function (Appointment $a) {
            $tx = Transaction::firstOrNew(['appointment_id' => $a->id]);
            $was = $tx->status;
            $paidOnline = filled($tx->gateway_payment_id); // money was taken by the gateway: it stays "paid" until it is refunded
            $tx->status = match (true) {
                $a->status === 'completed' => 'paid',
                in_array($a->status, ['cancelled', 'expired']) => match (true) {
                    $paidOnline && $was === 'paid' => 'paid', // refund job below flips it to "refunded"
                    in_array($was, ['paid', 'refunded']) => 'refunded',
                    default => 'cancelled',
                },
                default => $paidOnline && $was === 'paid' ? 'paid' : 'pending',
            };
            if (! $tx->exists || $a->wasChanged('patient_id')) {
                $tx->amount = (int) $a->doctor->fees; // fee is fixed at the time of booking
            }
            $tx->patient_id = $a->patient_id;
            $tx->doctor_id = $a->doctor_id;
            $tx->reference ??= 'TXN' . str_pad((string) $a->id, 6, '0', STR_PAD_LEFT);
            $tx->paid_at = match ($tx->status) { 'paid' => $tx->paid_at ?? now(), 'refunded' => $tx->paid_at, default => null };
            $tx->save();

            // a visit that was paid online is cancelled / expired: give the money back (once)
            if ($paidOnline && $was === 'paid' && in_array($a->status, ['cancelled', 'expired']) && blank($tx->refund_id)
                && Cache::add('refund:queued:' . $tx->id, 1, 600)) {
                RefundPayment::dispatch($tx->id)->afterCommit();
            }
        });
    }
}
