@extends('layouts.admin')
@section('title', 'Doctor Dashboard')
@section('content')
    @include('partials.kyc-banner', ['doctor' => $doctor])
    @include('partials.hero', [
        'title' => 'Welcome, ' . auth()->user()->name,
        'sub' => $next
            ? 'Your next appointment is with ' .
                $next->patient->name .
                ' on ' .
                $next->date->format('D, d M') .
                ' at ' .
                \Carbon\Carbon::parse($next->time)->format('g:i A') .
                '.'
            : 'No upcoming appointments right now. New requests will show up here.',
        'chips' => [
            ['calendar-day', $cards[1]['value'] . ' today'],
            ['hourglass-split', $cards[2]['value'] . ' pending requests'],
            ['star-fill', $rating['count'] ? $rating['avg'] . ' average rating' : 'No reviews yet'],
        ],
        'actions' => [
            ['Appointments', route('doctor.appointments'), 'calendar2-check', true],
            ['My Patients', route('doctor.patients'), 'people', false],
            ['My Profile', route('doctor.profile'), 'person-gear', false],
        ],
    ])

    <div class="row g-3 mb-3">
        @foreach ($cards as $c)
            <div class="col-sm-6 col-xl-3">@include('partials.stat-card', $c + ['delay' => $loop->index * 80])</div>
        @endforeach
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header"><span>Appointment Statistics ({{ now()->year }})</span></div>
                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-6 col-md-3"><div class="mini">All<b data-count="{{ array_sum($status) }}">{{ array_sum($status) }}</b></div></div>
                        <div class="col-6 col-md-3"><div class="mini">Pending<b data-count="{{ $status['pending'] + $status['confirmed'] + $status['in_progress'] }}">{{ $status['pending'] + $status['confirmed'] + $status['in_progress'] }}</b></div></div>
                        <div class="col-6 col-md-3"><div class="mini">Cancelled<b data-count="{{ $status['cancelled'] }}">{{ $status['cancelled'] }}</b></div></div>
                        <div class="col-6 col-md-3"><div class="mini">Completed<b data-count="{{ $status['completed'] }}">{{ $status['completed'] }}</b></div></div>
                    </div>
                    <div class="chart-box"><canvas id="apptChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header"><span>Upcoming Appointments</span><a class="small" href="{{ route('doctor.appointments') }}">View all</a></div>
                <div class="card-body pt-2">
                    @forelse($upcoming as $a)
                        <div class="list-row"><span class="avatar-sm">{{ $a->patient->initials }}</span>
                            <div class="flex-grow-1"><div class="fw-semibold">{{ $a->patient->name }}</div>
                                <div class="small text-secondary">{{ $a->reason ?: 'Consultation' }}</div></div>
                            <div class="text-end small"><div>{{ $a->date->format('d M') }}</div>
                                <div class="text-secondary">{{ \Carbon\Carbon::parse($a->time)->format('g:i A') }}</div></div>
                        </div>
                    @empty
                        <div class="empty"><i class="bi bi-calendar-check fs-3 d-block mb-1"></i>No upcoming appointments.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><span>Appointment Status</span></div>
                <div class="card-body">
                    @if (array_sum($status))
                        <div class="chart-sm"><canvas id="statusChart"></canvas></div>
                    @else
                        <div class="empty">No data yet.</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><span>Top Patients</span><a class="small" href="{{ route('doctor.patients') }}">View all</a></div>
                <div class="card-body pt-2">
                    <div class="small text-secondary mb-2">{{ $totalPatients }} {{ \Illuminate\Support\Str::plural('patient', $totalPatients) }} in total</div>
                    @forelse($topPatients as $t)
                        <div class="list-row"><span class="avatar-sm">{{ $t['patient']->initials }}</span>
                            <div class="flex-grow-1"><div class="fw-semibold">{{ $t['patient']->name }}</div>
                                <div class="small text-secondary">{{ $t['patient']->phone ?: $t['patient']->email }}</div></div>
                            <span class="tag tag-indigo">{{ $t['visits'] }} {{ $t['visits'] > 1 ? 'visits' : 'visit' }}</span>
                        </div>
                    @empty
                        <div class="empty">No patients yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><span>Patient Feedback</span><a class="small" href="{{ route('doctor.reviews') }}">All reviews</a></div>
                <div class="card-body pt-1">
                    @if ($rating['count'])
                        @include('partials.rating-summary', ['summary' => $rating])
                        @foreach ($latestReviews as $rv)
                            @include('partials.review-item', ['rv' => $rv])
                        @endforeach
                    @else
                        <div class="rv-empty"><i class="bi bi-star"></i>Ratings appear once patients review completed visits.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><span>Recent Appointments</span><a class="small" href="{{ route('doctor.appointments') }}">View all</a></div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr><th>Patient</th><th>Date &amp; Time</th><th>Fees</th><th>Status</th><th class="text-end">Action</th></tr>
                </thead>
                <tbody>
                    @forelse($recent as $a)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2"><span class="avatar-sm">{{ $a->patient->initials }}</span>
                                    <div>{{ $a->patient->name }}<div class="small text-secondary">{{ $a->patient->phone ?: $a->patient->email }}</div></div>
                                </div>
                            </td>
                            <td>{{ $a->date->format('d M Y') }}, {{ \Carbon\Carbon::parse($a->time)->format('g:i A') }}</td>
                            <td>&#8377;{{ number_format($a->fee) }}</td>
                            <td><span class="status status-{{ $a->status }}">{{ $a->statusLabel() }}</span></td>
                            <td class="text-end">
                                @if ($a->status === 'pending')
                                    <form method="post" action="{{ route('doctor.appointments.update', $a) }}" class="d-inline">@csrf @method('PATCH')
                                        <input type="hidden" name="status" value="confirmed">
                                        <button class="btn btn-sm btn-primary">Confirm</button></form>
                                @elseif (in_array($a->status, ['confirmed', 'in_progress']))
                                    <form method="post" action="{{ route('doctor.appointments.update', $a) }}" class="d-inline">@csrf @method('PATCH')
                                        <input type="hidden" name="status" value="completed">
                                        <button class="btn btn-sm btn-outline-success" @disabled(! $a->canComplete()) title="{{ $a->canComplete() ? '' : 'Can be completed on the appointment date' }}">Mark completed</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">No appointments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        const c = @json($chart);
        new Chart(document.getElementById('apptChart'), {
            type: 'bar',
            data: {
                labels: c.labels,
                datasets: [
                    {label: 'Completed', data: c.completed, backgroundColor: x => MC.gradient(x.chart, MC.pc, MC.pc2)},
                    {label: 'Pending / Confirmed', data: c.open, backgroundColor: x => MC.gradient(x.chart, MC.acc, MC.alpha(MC.acc, .55))},
                    {label: 'Cancelled', data: c.cancelled, backgroundColor: '#f87171'}
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                animation: {delay: x => x.dataIndex * 45},
                scales: {
                    x: {stacked: true, grid: {display: false}},
                    y: {stacked: true, beginAtZero: true, ticks: {precision: 0}, grid: {color: 'rgba(120,125,160,.15)'}}
                },
                plugins: {legend: {position: 'bottom'}}
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
                    borderWidth: 0, hoverOffset: 8
                }]
            },
            options: {responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: {legend: {position: 'bottom'}}}
        });
    </script>
@endpush