@extends('layouts.admin')
@section('title', $patient->name)
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="page-title"><a class="text-decoration-none text-secondary" href="{{ route('admin.patients') }}">Patients</a>
            / {{ $patient->name }}</h1>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="card h-100 fx">
                <div class="card-body text-center">
                    <span class="avatar-sm avatar-lg mb-2">{{ $patient->initials }}</span>
                    <h2 class="h6 mb-0">{{ $patient->name }}</h2>
                    <div class="text-secondary small mb-3">Patient since {{ $patient->created_at->format('d M Y') }}</div>
                    <div class="text-start small">
                        <div class="avail-row"><span class="text-secondary">Email</span><span>{{ $patient->email }}</span>
                        </div>
                        <div class="avail-row"><span
                                class="text-secondary">Phone</span><span>{{ $patient->phone ?: '-' }}</span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="row g-3">
                @foreach ([['Total visits', array_sum($status), 'calendar2-check', 'indigo'], ['Completed', $status['completed'], 'check2-circle', 'green'], ['Cancelled', $status['cancelled'], 'x-circle', 'red'], ['Fees paid', $spent, 'currency-rupee', 'amber', '₹']] as $i => $c)
                    <div class="col-sm-6">@include('partials.stat-card', [
                        'label' => $c[0],
                        'value' => $c[1],
                        'icon' => $c[2],
                        'color' => $c[3],
                        'prefix' => $c[4] ?? '',
                        'delay' => $i * 80,
                    ])</div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header">Appointment history</div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Doctor</th>
                        <th>Date &amp; Time</th>
                        <th>Reason</th>
                        <th>Fees</th>
                        <th>Status</th>
                        <th>Prescription</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($appts as $a)
                        <tr>
                            <td>{{ $a->doctor->user->name }}<div class="small text-secondary">
                                    {{ $a->doctor->speciality->name }}</div>
                            </td>
                            <td>{{ $a->date->format('d M Y') }}, {{ \Carbon\Carbon::parse($a->time)->format('g:i A') }}
                            </td>
                            <td>{{ $a->reason ?: '-' }}</td>
                            <td>&#8377;{{ number_format($a->fee) }}</td>
                            <td><span class="status status-{{ $a->status }}">{{ $a->statusLabel() }}</span></td>
                            <td class="small" style="white-space:pre-line;max-width:220px">
                                @if ($a->hasPrescription())
                                    @include('partials.rx-buttons', ['a' => $a])
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if ($a->isOpen())
                                    <form method="post" action="{{ route('admin.appointments.update', $a) }}"
                                        onsubmit="return confirm('Cancel this appointment?')">@csrf @method('PATCH')<input
                                            type="hidden" name="status" value="cancelled"><button
                                            class="btn btn-sm btn-outline-danger">Cancel</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty<tr>
                            <td colspan="7" class="empty">This patient has no appointments yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
