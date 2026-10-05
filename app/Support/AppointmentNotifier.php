<?php
namespace App\Support;

use App\Mail\AppointmentMail;
use App\Models\Appointment;
use Illuminate\Support\Facades\Mail;

/** E-mails the patient and the doctor when an appointment is booked, confirmed, cancelled, completed or rescheduled. */
class AppointmentNotifier
{
    /** $event: booked | confirmed | cancelled | completed | rescheduled | link.  $by: patient | doctor | admin | system */
    public static function send(Appointment $a, string $event, string $by = 'system'): void
    {
        // runs after the page has been sent, so a slow mail server never delays the user
        app()->terminating(function () use ($a, $event, $by) {
            $a->unsetRelations()->load('doctor.user', 'doctor.speciality', 'patient', 'transaction');
            foreach (['patient' => $a->patient, 'doctor' => $a->doctor?->user] as $for => $user) {
                if (! $user || ! $user->email) continue;
                try {
                    Mail::to($user->email)->send(new AppointmentMail($a, $event, $for, $by));
                } catch (\Throwable $e) {
                    report($e); // a mail problem must never break booking
                }
            }
        });
    }
}
