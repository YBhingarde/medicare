<?php
namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * One mail for every appointment event (booked, confirmed, cancelled, completed, rescheduled, link, reminder_24h, reminder_2h).
 * $for = who receives it (patient | doctor), $by = who caused the change (patient | doctor | admin | system).
 * The public properties are passed to resources/views/emails/appointment.blade.php automatically.
 */
class AppointmentMail extends Mailable
{
    public function __construct(
        public Appointment $appointment,
        public string $event,
        public string $for,
        public string $by = 'system',
    ) {}

    public function envelope(): Envelope
    {
        $isDoctor = $this->for === 'doctor';
        $title = match ($this->event) {
            'booked' => $isDoctor ? 'New appointment request' : 'Appointment requested',
            'confirmed' => 'Appointment confirmed',
            'cancelled' => 'Appointment cancelled',
            'completed' => 'Visit completed',
            'rescheduled' => 'Appointment rescheduled',
            'link' => 'Meeting link added',
            'reminder_24h' => 'Reminder - appointment in 24 hours',
            'reminder_2h' => 'Reminder - appointment in 2 hours',
            default => 'Appointment update',
        };

        return new Envelope(subject: 'MediCare - ' . $title . ' (' . $this->appointment->date->format('d M Y') . ')');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.appointment');
    }
}
