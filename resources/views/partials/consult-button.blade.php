{{-- Room button of one Online appointment. Active from 10 minutes before the visit until it ends; not shown after that.
     $a = appointment, $who = 'doctor' | 'patient' --}}
@php
    $phase = $a->consultPhase();
    $label = $who === 'doctor' ? ($a->consult_started_at ? 'Open room' : 'Start consultation') : 'Join';
@endphp
@if (in_array($phase, ['waiting', 'live']))
    <a class="btn btn-sm btn-success" href="{{ route('consult.show', $a) }}"><i class="bi bi-camera-video"></i> {{ $label }}</a>
@elseif ($phase === 'early')
    <button type="button" class="btn btn-sm btn-outline-secondary" disabled
        title="Opens {{ \App\Models\Appointment::CONSULT_EARLY_MINUTES }} minutes before the visit, at {{ $a->consultOpensAt()->format('g:i A') }}"><i class="bi bi-camera-video"></i> {{ $label }}</button>
@endif
