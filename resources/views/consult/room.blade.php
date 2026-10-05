@php
    $isDoctor = $role === 'doctor';
    $other = $isDoctor ? $a->patient->name : $a->doctor->user->name;
    $when = $a->startsAt()->format('D, d M Y') . ' - ' . $a->startsAt()->format('g:i A');
@endphp
<!doctype html>
<html lang="en">
<head>@include('layouts.head')<title>Video consultation - MediCare</title>
<style>
    html, body { height: 100%; }
    body { display: flex; flex-direction: column; background: #f4f7fc; }
    .room-bar { background: #fff; border-bottom: 1px solid var(--line, #e8eaf0); padding: .6rem 1rem; display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; }
    .room-bar .brand { font-weight: 800; color: #2563eb; text-decoration: none; }
    .room-main { flex: 1; min-height: 0; display: flex; }
    .room-frame { flex: 1; border: 0; width: 100%; height: 100%; background: #111; }
    .room-card { margin: auto; max-width: 480px; width: calc(100% - 2rem); text-align: center; padding: 2.2rem 1.6rem; }
    .room-card .ic { font-size: 2.6rem; color: #2563eb; }
    .pulse { animation: pulse 1.6s ease-in-out infinite; } @keyframes pulse { 50% { opacity: .35; } }
</style></head>
<body>
<div class="room-bar">
    <a class="brand" href="{{ $isDoctor ? route('doctor.appointments') : route('patient.appointments') }}"><i class="bi bi-arrow-left"></i> MediCare</a>
    <div class="small"><b>{{ $other }}</b> <span class="text-secondary">- {{ $when }}</span></div>
    <span class="status status-{{ $a->status }} ms-1">{{ $a->statusLabel() }}</span>
    @if ($isDoctor && $phase === 'live')
        <div class="ms-auto d-flex gap-2">
            <a class="btn btn-sm btn-outline-primary" href="{{ route('doctor.prescription.edit', $a) }}" target="_blank" rel="noopener"><i class="bi bi-file-earmark-medical"></i> Prescription</a>
            <form method="post" action="{{ route('consult.complete', $a) }}" onsubmit="return confirm('Mark this visit as completed? The room closes for both of you.')">@csrf
                <button class="btn btn-sm btn-success"><i class="bi bi-check2-circle"></i> Complete visit</button></form>
        </div>
    @endif
</div>

@if ($errors->any())<div class="alert alert-danger m-3 mb-0">{{ $errors->first() }}</div>@endif

<div class="room-main">
@switch($phase)
    @case('live')
        <iframe class="room-frame" src="{{ $embed }}" title="Video consultation"
            allow="camera; microphone; fullscreen; display-capture; autoplay; clipboard-write"></iframe>
        @break

    @case('waiting')
        <div class="card room-card">
            @if ($isDoctor)
                <div class="ic"><i class="bi bi-camera-video"></i></div>
                <h5 class="mt-2">Ready to start?</h5>
                <p class="text-secondary">Patient: <b>{{ $a->patient->name }}</b>@if ($a->reason)<br><span class="small">{{ $a->reason }}</span>@endif</p>
                <form method="post" action="{{ route('consult.start', $a) }}">@csrf
                    <button class="btn btn-primary btn-lg"><i class="bi bi-play-circle"></i> Start consultation</button></form>
            @else
                <div class="ic pulse"><i class="bi bi-hourglass-split"></i></div>
                <h5 class="mt-2">Waiting for the doctor</h5>
                <p class="text-secondary mb-0">{{ $a->doctor->user->name }} has not started the consultation yet. This page opens the room by itself - please keep it open.</p>
            @endif
        </div>
        @break

    @case('early')
        <div class="card room-card">
            <div class="ic"><i class="bi bi-clock-history"></i></div>
            <h5 class="mt-2">Not started yet</h5>
            <p class="text-secondary mb-0">The room opens {{ \App\Models\Appointment::CONSULT_EARLY_MINUTES }} minutes before the visit, at <b>{{ $a->consultOpensAt()->format('g:i A') }}</b>. This page opens it by itself.</p>
        </div>
        @break

    @case('unconfirmed')
        <div class="card room-card">
            <div class="ic"><i class="bi bi-hourglass"></i></div>
            <h5 class="mt-2">Not confirmed yet</h5>
            <p class="text-secondary mb-0">The room is available after the doctor confirms this appointment.</p>
        </div>
        @break

    @default
        <div class="card room-card">
            <div class="ic"><i class="bi bi-{{ $a->status === 'completed' ? 'check2-circle' : 'x-circle' }}"></i></div>
            <h5 class="mt-2">{{ $a->status === 'cancelled' ? 'Appointment cancelled' : 'Visit ended' }}</h5>
            <p class="text-secondary">{{ $a->status === 'completed' ? 'This visit is completed. Thank you for choosing MediCare.' : 'This video room is closed.' }}</p>
            <a class="btn btn-primary" href="{{ $isDoctor ? route('doctor.appointments') : route('patient.appointments') }}">Back to appointments</a>
        </div>
@endswitch
</div>

@if ($phase !== 'ended')
<script>
// When the situation changes (room opens, doctor starts, doctor completes) reload, so both people always see the right screen.
(function () {
    var phase = @json($phase), url = @json(route('consult.state', $a));
    setInterval(function () {
        fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) { location.reload(); return null; } return r.json(); })
            .then(function (d) { if (d && d.phase !== phase) location.reload(); })
            .catch(function () {});
    }, 5000);
})();
</script>
@endif
</body>
</html>
