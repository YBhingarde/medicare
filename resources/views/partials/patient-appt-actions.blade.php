{{-- Patient actions for one appointment: Join (online), Reschedule, Cancel (both locked in the last few hours), Book again --}}
@php
    $canChange = $a->patientCanChange();
    $lockTip = 'Locked: changes are allowed only up to ' . \App\Models\Appointment::CANCEL_CUTOFF_HOURS . ' hours before the visit';
@endphp
@if ($a->mode === 'Online' && in_array($a->status, ['confirmed', 'in_progress']))
    @if ($a->usesAppRoom())
        {{-- own video room: active from 10 minutes before the visit until it ends --}}
        @include('partials.consult-button', ['a' => $a, 'who' => 'patient'])
    @elseif ($a->meeting_link)
        <a class="btn btn-sm btn-success" href="{{ $a->meeting_link }}" target="_blank" rel="noopener"><i class="bi bi-camera-video"></i> Join</a>
    @endif
@endif
@if ($a->awaitingPayment())
    <a class="btn btn-sm btn-success" href="{{ route('patient.pay', $a) }}"><i class="bi bi-credit-card"></i> Pay &#8377;{{ number_format($a->fee) }}</a>
@endif
@if ($a->isOpen())
    @if ($canChange)
        <a class="btn btn-sm btn-outline-primary" href="{{ route('patient.reschedule', $a) }}"><i class="bi bi-calendar2-week"></i> Reschedule</a>
        <form class="d-inline" method="post" action="{{ route('patient.cancel', $a) }}" onsubmit="return confirm('Cancel this appointment?')">@csrf
            <button class="btn btn-sm btn-outline-danger">Cancel</button></form>
    @else
        <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="{{ $lockTip }}"><i class="bi bi-lock"></i> Reschedule</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="{{ $lockTip }}"><i class="bi bi-lock"></i> Cancel</button>
    @endif
@elseif ($a->doctor->approved)
    <a class="btn btn-sm btn-outline-primary" href="{{ route('doctors.show', $a->doctor) }}?rebook=1"><i class="bi bi-arrow-repeat"></i> Book again</a>
@endif
