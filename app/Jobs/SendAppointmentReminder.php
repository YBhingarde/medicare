<?php
namespace App\Jobs;

use App\Mail\AppointmentMail;
use App\Models\Appointment;
use App\Support\MeetingLink;
use App\Support\Messenger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * One reminder on ONE channel (mail-patient | mail-doctor | sms | whatsapp), so a failing SMS gateway
 * never blocks the e-mail and a retry never sends the other channels twice.
 * $label: '24h' | '2h'.
 */
class SendAppointmentReminder implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $appointmentId, public string $label, public string $channel) {}

    public function backoff(): array
    {
        return [60, 300];
    }

    /** Channels that are switched on right now. */
    public static function channels(): array
    {
        return array_values(array_filter([
            'mail-patient', 'mail-doctor',
            Messenger::smsReady() ? 'sms' : null,
            Messenger::whatsappReady() ? 'whatsapp' : null,
        ]));
    }

    public function handle(): void
    {
        $a = Appointment::with('doctor.user', 'doctor.speciality', 'patient', 'transaction')->find($this->appointmentId);
        // cancelled, moved (back to pending) or already over since the job was queued: nothing to remind
        if (! $a || $a->status !== 'confirmed' || $a->startsAt()->isPast()) return;

        MeetingLink::ensure($a); // old online visits without a link get one before the reminder goes out

        $event = 'reminder_' . $this->label;
        switch ($this->channel) {
            case 'mail-patient':
                if ($a->patient?->email) Mail::to($a->patient->email)->send(new AppointmentMail($a, $event, 'patient'));
                break;
            case 'mail-doctor':
                if ($a->doctor?->user?->email) Mail::to($a->doctor->user->email)->send(new AppointmentMail($a, $event, 'doctor'));
                break;
            case 'sms':
            case 'whatsapp':
                $this->phone($a);
                break;
        }
    }

    protected function phone(Appointment $a): void
    {
        $to = Messenger::e164($a->patient?->phone);
        if (! $to) {
            Log::info('Reminder skipped: patient has no usable phone number', ['appointment' => $a->id]);
            return;
        }

        $when = $a->startsAt()->format('D, d M \a\t g:i A');
        $doctor = $a->doctor->user->name;
        $join = ($a->mode === 'Online' && $a->meeting_link) ? $a->meeting_link : ($a->mode ?: 'In-person') . ' visit';

        if ($this->channel === 'sms') {
            Messenger::sms($to, "MediCare reminder: your appointment with {$doctor} is on {$when}." . ($a->mode === 'Online' && $a->meeting_link ? " Join: {$a->meeting_link}" : ''));
        } else {
            Messenger::whatsapp($to, "MediCare reminder: your appointment with {$doctor} is on {$when}. {$join}", ['1' => $doctor, '2' => $when, '3' => $join]);
        }
    }
}
