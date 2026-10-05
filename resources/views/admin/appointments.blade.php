@extends('layouts.admin')
@section('title', 'Appointments')
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="page-title">Appointments</h1>
        <form method="get" class="d-flex flex-wrap gap-2">
            <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Patient name" style="width:160px">
            <input type="date" name="date" value="{{ request('date') }}" class="form-control form-control-sm" aria-label="Date" style="width:150px">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Filter by status" style="width:150px">
                <option value="">All statuses</option>
                @foreach (['pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'expired'] as $st)
                    <option value="{{ $st }}" @selected(request('status') === $st)>{{ $st === 'expired' ? 'Expired / No-show' : str_replace('_', ' ', ucfirst($st)) }} ({{ $counts[$st] }})</option>
                @endforeach
            </select>
            <button class="btn btn-primary btn-sm">Filter</button>
            @if (request()->hasAny(['q', 'date', 'status']))
                <a class="btn btn-light btn-sm" href="{{ route('admin.appointments') }}">Clear</a>
            @endif
        </form>
    </div>
    <div class="card fx">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr><th>Patient</th><th>Doctor</th><th>Date</th><th>Time</th><th>Mode</th><th>Status</th><th>Prescription</th><th class="text-end">Action</th></tr>
                </thead>
                <tbody>
                    @forelse($appointments as $a)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2"><span class="avatar-sm">{{ $a->patient->initials }}</span>
                                    <div>{{ $a->patient->name }}<div class="small text-secondary">{{ $a->reason ?: $a->patient->phone }}</div></div>
                                </div>
                            </td>
                            <td>
                                <div>{{ $a->doctor->user->name }}<div class="small text-secondary">{{ $a->doctor->speciality->name }}</div></div>
                            </td>
                            <td>{{ $a->date->format('d M Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($a->time)->format('g:i A') }}</td>
                            <td>{{ $a->mode ?? 'In-person' }}</td>
                            <td><span class="status status-{{ $a->status }}">{{ $a->statusLabel() }}</span>@if ($a->status === 'cancelled' && $a->cancelled_by)<div class="small text-secondary">by {{ $a->cancelled_by }}</div>@endif</td>
                            <td class="small" style="white-space:pre-line;max-width:240px">@if ($a->hasPrescription())@include('partials.rx-buttons', ['a' => $a])@else - @endif</td>
                            <td class="text-end text-nowrap">
                                @if ($a->isOpen())
                                    <form method="post" action="{{ route('admin.appointments.update', $a) }}" class="d-inline">@csrf @method('PATCH')
                                        <input type="hidden" name="status" value="{{ $a->status === 'pending' ? 'confirmed' : 'completed' }}">
                                        <button class="btn btn-sm btn-primary" @disabled($a->status !== 'pending' && ! $a->canComplete())
                                            title="{{ $a->status !== 'pending' && ! $a->canComplete() ? 'Can be completed on the appointment date' : '' }}">{{ $a->status === 'pending' ? 'Confirm' : 'Mark completed' }}</button></form>
                                    <form method="post" action="{{ route('admin.appointments.update', $a) }}" class="d-inline"
                                        onsubmit="return confirm('Cancel this appointment?')">@csrf @method('PATCH')
                                        <input type="hidden" name="status" value="cancelled">
                                        <button class="btn btn-sm btn-outline-danger">Cancel</button></form>
                                @else
                                    <span class="text-secondary small">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty">No appointments found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $appointments->links('pagination::bootstrap-5') }}</div>
@endsection