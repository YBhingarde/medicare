@extends('layouts.admin')
@section('title', 'My Appointments')
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="page-title">My Appointments</h1>
        <form method="get" class="d-flex flex-wrap gap-2">
            <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Patient name" style="width:160px" aria-label="Search patient">
            <input type="date" name="date" value="{{ request('date') }}" class="form-control form-control-sm" aria-label="Date" style="width:150px">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Filter by status" style="width:150px">
                <option value="">All statuses</option>
                @foreach (['pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'expired'] as $st)
                    <option value="{{ $st }}" @selected(request('status') === $st)>{{ $st === 'expired' ? 'Expired / No-show' : str_replace('_', ' ', ucfirst($st)) }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary btn-sm">Search</button>
            @if (request()->hasAny(['q', 'date', 'status']))
                <a class="btn btn-light btn-sm" href="{{ route('doctor.appointments') }}">Clear</a>
            @endif
        </form>
    </div>
    <div class="card fx">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr><th>Patient</th><th>Date</th><th>Time</th><th>Mode</th><th>Status</th><th>Prescription</th><th class="text-end">Action</th></tr>
                </thead>
                <tbody>
                    @forelse($appointments as $a)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2"><span class="avatar-sm">{{ $a->patient->initials }}</span>
                                    <div>{{ $a->patient->name }}<div class="small text-secondary">{{ $a->reason ?: $a->patient->phone }}</div></div>
                                </div>
                            </td>
                            <td>{{ $a->date->format('d M Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($a->time)->format('g:i A') }}</td>
                            <td>
                                {{ $a->mode ?? 'In-person' }}
                                @if ($a->mode === 'Online' && $a->meeting_link && ! $a->usesAppRoom())
                                    <div class="small"><a href="{{ $a->meeting_link }}" target="_blank" rel="noopener" class="text-decoration-none"><i class="bi bi-camera-video"></i> Link</a></div>
                                @elseif ($a->mode === 'Online')
                                    <div class="small text-secondary"><i class="bi bi-camera-video"></i> MediCare room</div>
                                @endif
                            </td>
                            <td>
                                <span class="status status-{{ $a->status }}">{{ $a->status === 'expired' ? 'Expired' : $a->statusLabel() }}</span>
                                @if ($a->status === 'expired')<div class="small text-secondary">No-show</div>@endif
                                @if ($a->status === 'cancelled' && $a->cancelled_by)<div class="small text-secondary">by {{ $a->cancelled_by }}</div>@endif
                            </td>
                            <td class="small">@if ($a->hasPrescription())<a href="{{ route('prescription.show', $a) }}" class="text-decoration-none"><i class="bi bi-file-earmark-medical"></i> View</a><div class="text-secondary">{{ $a->rxNumber() }}</div>@else - @endif</td>
                            <td class="text-end text-nowrap">
                                @if ($a->status === 'pending')
                                    <form method="post" action="{{ route('doctor.appointments.update', $a) }}" class="d-inline">@csrf @method('PATCH')
                                        <input type="hidden" name="status" value="confirmed">
                                        <button class="btn btn-sm btn-primary">Confirm</button></form>
                                @elseif (in_array($a->status, ['confirmed', 'in_progress']))
                                    <form method="post" action="{{ route('doctor.appointments.update', $a) }}" class="d-inline">@csrf @method('PATCH')
                                        <input type="hidden" name="status" value="completed">
                                        <button class="btn btn-sm btn-success" @disabled(! $a->canComplete())
                                            title="{{ $a->canComplete() ? '' : 'Can be marked completed on the appointment date (' . $a->date->format('d M') . ')' }}">Mark completed</button></form>
                                @endif
                                @if ($a->isOpen())
                                    <form method="post" action="{{ route('doctor.appointments.update', $a) }}" class="d-inline"
                                        onsubmit="return confirm('Cancel this appointment? The patient will be e-mailed.')">@csrf @method('PATCH')
                                        <input type="hidden" name="status" value="cancelled">
                                        <button class="btn btn-sm btn-outline-danger">Cancel</button></form>
                                @endif
                                @if ($a->mode === 'Online' && $a->usesAppRoom() && in_array($a->status, ['confirmed', 'in_progress']))
                                    @include('partials.consult-button', ['a' => $a, 'who' => 'doctor'])
                                @endif
                                @if ($a->mode === 'Online' && $a->isOpen())
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal"
                                        data-bs-target="#link{{ $a->id }}"><i class="bi bi-camera-video"></i> Meeting link</button>
                                @endif
                                @if (in_array($a->status, ['confirmed', 'in_progress', 'completed']))
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('doctor.prescription.edit', $a) }}"><i class="bi bi-file-earmark-medical"></i> {{ $a->hasPrescription() ? 'Edit Rx' : 'Write Rx' }}</a>
                                @endif
                                @if ($a->hasPrescription())
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('prescription.pdf', $a) }}" title="Download PDF"><i class="bi bi-file-earmark-pdf"></i></a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty">No appointments found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $appointments->links('pagination::bootstrap-5') }}</div>

    @foreach ($appointments as $a)
        @if ($a->mode === 'Online' && $a->isOpen())
            <div class="modal fade" id="link{{ $a->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog"><form method="post" action="{{ route('doctor.appointments.update', $a) }}" class="modal-content">@csrf @method('PATCH')
                    <input type="hidden" name="status" value="{{ $a->status }}">
                    <div class="modal-header"><h5 class="modal-title">Meeting link - {{ $a->patient->name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                    <div class="modal-body">
                        <label class="form-label small fw-semibold">Own video-call link (optional)</label>
                        <input type="url" name="meeting_link" maxlength="255" class="form-control" placeholder="https://meet.google.com/abc-defg-hij" value="{{ $a->usesAppRoom() ? '' : $a->meeting_link }}">
                        <div class="form-text">Leave this empty to hold the visit in the MediCare video room (opens 10 minutes before the visit, "Start consultation" button). Paste your own Google Meet / Zoom link only if you want to use that instead - then the visit is not tracked here.</div>
                    </div>
                    <div class="modal-footer">
                        @if (\App\Support\MeetingLink::isGoogleReady())
                            <button type="submit" name="generate_meet" value="1" class="btn btn-outline-success me-auto"><i class="bi bi-google"></i> Create Google Meet link</button>
                        @endif
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button class="btn btn-primary">Save link</button></div>
                </form></div>
            </div>
        @endif
    @endforeach
@endsection
