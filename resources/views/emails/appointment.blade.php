@php
    $a = $appointment;
    $isDoctor = $for === 'doctor';
    $who = $isDoctor ? $a->patient->name : $a->doctor->user->name;
    $me = $isDoctor ? $a->doctor->user->name : $a->patient->name;
    $byText = match ($by) { 'patient' => 'the patient', 'doctor' => 'the doctor', 'admin' => 'the MediCare team', default => 'the system' };
    [$title, $text, $color] = match ($event) {
        'booked' => $isDoctor
            ? ['New appointment request', "$who has requested an appointment. Please confirm it from your dashboard.", '#2563eb']
            : ['Appointment requested', "Your request with $who has been sent. You will get another e-mail when the doctor confirms it.", '#2563eb'],
        'confirmed' => ['Appointment confirmed', $isDoctor ? "You confirmed the appointment with $who." : "$who has confirmed your appointment.", '#16a34a'],
        'cancelled' => ['Appointment cancelled', "This appointment was cancelled by $byText.", '#dc2626'],
        'completed' => ['Visit completed', $isDoctor ? "The visit with $who is marked completed." : "Your visit with $who is marked completed. Thank you for choosing MediCare - you can rate the doctor from your dashboard.", '#16a34a'],
        'rescheduled' => ['Appointment rescheduled', $isDoctor ? "$who has moved the appointment to the time below. It is pending your confirmation again." : "Your appointment was moved to the time below. The doctor will confirm it again.", '#d97706'],
        'link' => ['Meeting link added', $isDoctor ? 'The meeting link was saved for this online visit.' : "$who added the video-call link for your online visit.", '#2563eb'],
        'reminder_24h' => ['Appointment reminder', $isDoctor ? "You have an appointment with $who in about 24 hours." : "This is a reminder: your appointment with $who is in about 24 hours.", '#d97706'],
        'reminder_2h' => ['Appointment in 2 hours', $isDoctor ? "Your appointment with $who starts in about 2 hours." : "Your appointment with $who starts in about 2 hours. Please be ready on time.", '#d97706'],
        default => ['Appointment update', 'There is an update on your appointment.', '#2563eb'],
    };
    $rows = [
        ['Doctor', $a->doctor->user->name . ' (' . $a->doctor->speciality->name . ')'],
        ['Patient', $a->patient->name],
        ['Date', $a->date->format('l, d M Y')],
        ['Time', $a->startsAt()->format('g:i A')],
        ['Visit type', $a->mode ?: 'In-person'],
        ['Fee', '₹' . number_format($a->fee)],
    ];
    if ($a->reason) $rows[] = ['Reason', $a->reason];
    $joinUrl = ($a->mode === 'Online' && $a->meeting_link && in_array($a->status, ['pending', 'confirmed'])) ? $a->meeting_link : null;
    if ($joinUrl) $rows[] = ['Meeting link', $joinUrl];
@endphp
<!doctype html>
<html lang="en">
<body style="margin:0;padding:0;background:#f4f7fc;font-family:Arial,Helvetica,sans-serif;color:#1f2333">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7fc;padding:28px 12px"><tr><td align="center">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e8eaf0">
        <tr><td style="background:{{ $color }};padding:20px 28px;color:#ffffff;font-size:20px;font-weight:bold">MediCare - {{ $title }}</td></tr>
        <tr><td style="padding:28px">
            <p style="margin:0 0 6px;font-size:16px">Hello {{ $me }},</p>
            <p style="margin:0 0 20px;font-size:14px;line-height:1.6;color:#4a5068">{{ $text }}</p>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;border-collapse:collapse">
                @foreach ($rows as [$l, $v])
                    <tr>
                        <td style="padding:9px 0;border-bottom:1px solid #eef0f6;color:#7b8194;width:34%">{{ $l }}</td>
                        <td style="padding:9px 0;border-bottom:1px solid #eef0f6;font-weight:bold">{{ $v }}</td>
                    </tr>
                @endforeach
            </table>
            @if ($joinUrl)
                <p style="margin:22px 0 0"><a href="{{ $joinUrl }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:bold;font-size:14px;padding:12px 22px;border-radius:10px">Join video consultation</a></p>
            @endif
            <p style="margin:20px 0 0;font-size:12px;color:#9aa0b4">Booking ref: TXN{{ str_pad((string) $a->id, 6, '0', STR_PAD_LEFT) }}</p>
        </td></tr>
    </table>
    <p style="margin:14px 0 0;font-size:12px;color:#9aa0b4">&copy; {{ date('Y') }} MediCare</p>
</td></tr></table>
</body>
</html>
