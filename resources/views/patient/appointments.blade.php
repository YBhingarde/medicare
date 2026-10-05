@extends('layouts.admin')
@section('title', 'My appointments')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="page-title">My Appointments</h1><a class="btn btn-primary btn-sm" href="{{ route('doctors') }}"><i
                class="bi bi-plus-lg"></i> New Appointment</a>
    </div>
    @if ($filter || $status)
        <div class="mb-3 d-flex align-items-center gap-2">
            <span class="tag">Showing: {{ $filter ? 'Upcoming' : ($status === 'expired' ? 'Expired' : str_replace('_', ' ', ucfirst($status))) }}</span>
            <a class="btn btn-light btn-sm" href="{{ route('patient.appointments') }}">Show all</a>
        </div>
    @endif
    <div class="card fx">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Doctor</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Mode</th>
                        <th>Status</th>
                        <th>Prescription</th>
                        <th>Feedback</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($appointments as $a)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2"><span
                                        class="avatar-sm">{{ $a->doctor->user->initials }}</span>
                                    <div>{{ $a->doctor->user->name }}<div class="small text-secondary">
                                            {{ $a->doctor->speciality->name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $a->date->format('d M Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($a->time)->format('g:i A') }}</td>
                            <td>{{ $a->mode ?? 'In-person' }}</td>
                            <td><span class="status status-{{ $a->status }}">{{ $a->status === 'expired' ? 'Expired' : $a->statusLabel() }}</span>
                                @if ($a->status === 'expired')<div class="small text-secondary">Not confirmed in time</div>@endif</td>
                            <td class="small text-nowrap">@if ($a->hasPrescription())@include('partials.rx-buttons', ['a' => $a])@else - @endif</td>
                            <td>@include('partials.rate-button', ['a' => $a])</td>
                            <td class="text-end text-nowrap">@include('partials.patient-appt-actions', ['a' => $a])</td>
                        </tr>
                    @empty <tr>
                            <td colspan="8" class="empty">{{ $filter || $status ? 'No appointments match this filter.' : 'No appointments yet.' }} <a href="{{ route('doctors') }}">Find a
                                    doctor</a> to book {{ $filter || $status ? 'one' : 'your first one' }}.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection