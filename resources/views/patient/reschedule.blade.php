@extends('layouts.admin')
@section('title', 'Reschedule appointment')
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="page-title">Reschedule appointment</h1>
        <a class="btn btn-light btn-sm" href="{{ route('patient.appointments') }}"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card fx p-3">
                <div class="d-flex align-items-center gap-2 mb-3">@include('partials.doc-photo-sm', ['d' => $doctor])
                    <div><b>{{ $doctor->user->name }}</b><div class="small text-secondary">{{ $doctor->speciality->name }}</div></div>
                </div>
                <div class="small text-secondary">Current booking</div>
                <div class="fw-semibold mb-1">{{ $a->date->format('d M Y') }}, {{ $a->startsAt()->format('g:i A') }}</div>
                <span class="status status-{{ $a->status }}">{{ $a->statusLabel() }}</span>
                <hr>
                <p class="small text-secondary mb-0"><i class="bi bi-info-circle"></i> After rescheduling, the doctor has to confirm the new time again.
                    You can change a booking only up to {{ \App\Models\Appointment::CANCEL_CUTOFF_HOURS }} hours before the visit.</p>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card fx p-3">
                <form method="get" class="mb-3" style="max-width:240px">
                    <label class="form-label small fw-semibold">Choose new date</label>
                    <input type="date" name="date" value="{{ $date }}" min="{{ now()->toDateString() }}" max="{{ $maxDate }}"
                        class="form-control" onchange="this.form.submit()">
                </form>

                @if ($onLeave)
                    <div class="alert alert-warning py-2 small mb-0"><i class="bi bi-calendar-x"></i> The doctor is on leave on this date. Try another day.</div>
                @elseif (! $board)
                    <div class="text-secondary small"><i class="bi bi-calendar-x"></i> No slots on this date. Try another day.</div>
                @else
                    <form method="post" action="{{ route('patient.reschedule.update', $a) }}">@csrf
                        <input type="hidden" name="date" value="{{ $date }}">
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            @foreach ($board as $s)
                                @php $current = $date === $a->date->toDateString() && $s['time'] === substr((string) $a->time, 0, 5); @endphp
                                <input type="radio" class="btn-check" name="time" id="rt{{ $loop->index }}" value="{{ $s['time'] }}" required @disabled($s['full'] || $current)>
                                <label for="rt{{ $loop->index }}" class="btn btn-sm btn-outline-primary {{ $s['full'] || $current ? 'disabled' : '' }}">
                                    {{ \Carbon\Carbon::parse($s['time'])->format('g:i A') }}
                                    @if ($current)<small>(current)</small>@elseif ($s['full'])<small>Full</small>@endif
                                </label>
                            @endforeach
                        </div>
                        <button class="btn btn-primary"><i class="bi bi-calendar2-check"></i> Confirm new time</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection
