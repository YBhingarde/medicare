@extends('layouts.admin')
@section('title', 'Patient Dashboard')
@section('content')
    @include('partials.hero', [
        'title' => 'Hi, ' . auth()->user()->name,
        'sub' => $next
            ? 'Your next visit is with ' .
                $next->doctor->user->name .
                ' on ' .
                $next->date->format('D, d M') .
                ' at ' .
                \Carbon\Carbon::parse($next->time)->format('g:i A') .
                '.'
            : 'Stay on top of your health - book a trusted doctor in a few taps.',
        'chips' => [
            ['clock-history', $cards[1]['value'] . ' upcoming'],
            ['check2-circle', $cards[2]['value'] . ' consultations done'],
            ['file-earmark-medical', $prescriptions->count() . ' prescriptions'],
        ],
        'actions' => [
            ['New Appointment', route('doctors'), 'plus-lg', true],
            ['My Profile', route('patient.profile'), 'person-circle', false],
        ],
    ])

    <div class="row g-3 mb-3">
        @foreach ($cards as $c)
            <div class="col-sm-6 col-xl-3">@include('partials.stat-card', $c + ['delay' => $loop->index * 80])</div>
        @endforeach
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><span>My Doctors</span></div>
                <div class="card-body pt-2">
                    @forelse($myDoctors as $m)
                        <div class="list-row"><span class="avatar-sm">{{ $m['doctor']->user->initials }}</span>
                            <div class="flex-grow-1"><a class="fw-semibold text-decoration-none"
                                    href="{{ route('doctors.show', $m['doctor']) }}">{{ $m['doctor']->user->name }}</a>
                                <div class="small text-secondary">{{ $m['doctor']->speciality->name }}</div>
                            </div>
                            <span class="tag">{{ $m['visits'] }} {{ $m['visits'] > 1 ? 'Bookings' : 'Booking' }}</span>
                            @if ($m['doctor']->approved)
                                <a href="{{ route('doctors.show', $m['doctor']) }}?rebook=1" class="btn btn-sm btn-primary ms-2"
                                    title="Book another appointment with this doctor"><i class="bi bi-arrow-repeat"></i> Book again</a>
                            @endif
                        </div>
                    @empty<div class="empty"><i class="bi bi-person-heart fs-3 d-block mb-1"></i>No doctors yet.<br><a
                                href="{{ route('doctors') }}">Find a doctor</a></div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><span>Prescriptions</span></div>
                <div class="card-body pt-2">
                    @forelse($prescriptions as $a)
                        <div class="list-row"><span class="ico ico-indigo" style="width:34px;height:34px"><i
                                    class="bi bi-file-earmark-medical"></i></span>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $a->doctor->user->name }}</div>
                                <div class="small text-secondary">{{ $a->date->format('d M Y') }}</div>
                                <div class="small mt-1" style="white-space:pre-line">
                                    {{ \Illuminate\Support\Str::limit($a->prescription, 90) }}</div>
                                <div class="mt-1">@include('partials.rx-buttons', ['a' => $a])</div>
                            </div>
                        </div>
                    @empty<div class="empty"><i class="bi bi-file-earmark-medical fs-3 d-block mb-1"></i>No
                            prescriptions yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><span>Recent Activity</span></div>
                <div class="card-body">
                    @if ($activity->count())
                        <div class="timeline">
                            @foreach ($activity as $a)
                                <div class="t"><b>{{ $a->statusLabel() }}</b> - appointment with
                                    {{ $a->doctor->user->name }}<div class="text-secondary">
                                        {{ $a->date->format('d M Y') }},
                                        {{ \Carbon\Carbon::parse($a->time)->format('g:i A') }}</div>
                                </div>
                            @endforeach
                        </div>
                    @else<div class="empty">Nothing yet.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header"><span>Consultation by Department</span></div>
                <div class="card-body">
                    @if ($departments->count())
                    <div class="chart-box"><canvas id="deptChart"></canvas></div>@else<div class="empty">Book your
                            first appointment to see this chart.</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><span>Appointment Status</span></div>
                <div class="card-body">
                    @if (array_sum($status))
                    <div class="chart-sm"><canvas id="statusChart"></canvas></div>@else<div class="empty">No data yet.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><span>Recent Appointments</span><a class="small"
                href="{{ route('patient.appointments') }}">View all</a></div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Doctor</th>
                        <th>Date &amp; Time</th>
                        <th>Fees</th>
                        <th>Status</th>
                        <th class="text-end">Feedback</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recent as $a)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2"><span
                                        class="avatar-sm">{{ $a->doctor->user->initials }}</span>
                                    <div>{{ $a->doctor->user->name }}<div class="small text-secondary">
                                            {{ $a->doctor->speciality->name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $a->date->format('d M Y') }}, {{ \Carbon\Carbon::parse($a->time)->format('g:i A') }}
                            </td>
                            <td>&#8377;{{ number_format($a->fee) }}</td>
                            <td><span class="status status-{{ $a->status }}">{{ $a->statusLabel() }}</span></td>
                            <td class="text-end">@include('partials.rate-button', ['a' => $a])</td>
                        </tr>
                    @empty<tr>
                            <td colspan="5" class="empty">No appointments yet. <a href="{{ route('doctors') }}">Find a
                                    doctor</a> to book your first one.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        const d = @json($departments);
        if (document.getElementById('deptChart')) new Chart(document.getElementById('deptChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(d),
                datasets: [{
                    label: 'Consultations',
                    data: Object.values(d),
                    backgroundColor: x => MC.gradient(x.chart, MC.pc2, MC.pc)
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    delay: x => x.dataIndex * 80
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        },
                        grid: {
                            color: 'rgba(120,125,160,.15)'
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        const s = @json($status);
        if (document.getElementById('statusChart')) new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Confirmed', 'Completed', 'Cancelled'],
                datasets: [{
                    data: [s.pending, s.confirmed, s.completed, s.cancelled],
                    backgroundColor: ['#f59e0b', '#3b82f6', '#22a55e', '#ef4444'],
                    borderWidth: 0,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
@endpush