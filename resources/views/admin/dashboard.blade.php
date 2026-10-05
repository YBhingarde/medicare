@extends('layouts.admin')
@section('title', 'Admin Dashboard')
@section('content')
    @include('partials.hero', [
        'title' => 'Welcome, ' . auth()->user()->name,
        'sub' => 'Here is what is happening across MediCare today.',
        'chips' => [
            ['person-badge', $cards[0]['value'] . ' doctors'],
            ['people', $cards[1]['value'] . ' patients'],
            ['star-fill', $rating['count'] ? $rating['avg'] . ' average rating' : 'No reviews yet'],
        ],
        'actions' => [
            ['Add Doctor', route('admin.doctors.create'), 'plus-lg', true],
            ['Appointments', route('admin.appointments'), 'calendar2-check', false],
            ['Reviews', route('admin.reviews'), 'star', false],
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
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><span>Upcoming Appointments</span><a class="small" href="{{ route('admin.appointments') }}">View all</a></div>
                <div class="card-body pt-2">
                    @forelse($upcoming as $a)
                        <div class="list-row"><span class="avatar-sm">{{ $a->patient->initials }}</span>
                            <div class="flex-grow-1"><div class="fw-semibold">{{ $a->patient->name }}</div>
                                <div class="small text-secondary">with {{ $a->doctor->user->name }}</div></div>
                            <div class="text-end small"><div>{{ $a->date->format('d M') }}</div>
                                <div class="text-secondary">{{ \Carbon\Carbon::parse($a->time)->format('g:i A') }}</div></div>
                        </div>
                    @empty
                        <div class="empty"><i class="bi bi-calendar-check fs-3 d-block mb-1"></i>No upcoming appointments.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><span>Popular Doctors</span><a class="small" href="{{ route('admin.doctors') }}">View all</a></div>
                <div class="card-body pt-2">
                    @forelse($popular as $d)
                        <div class="list-row">@include('partials.doc-photo-sm', ['d' => $d])
                            <div class="flex-grow-1"><a class="fw-semibold text-decoration-none" href="{{ route('admin.doctors.show', $d) }}">{{ $d->user->name }}</a>
                                <div class="small text-secondary">{{ $d->speciality->name }}</div></div>
                            <div class="text-end small">
                                <span class="tag tag-indigo">{{ $d->appointments_count }} {{ $d->appointments_count == 1 ? 'booking' : 'bookings' }}</span>
                                @if ($d->reviews_count)
                                    <div class="text-secondary mt-1"><i class="bi bi-star-fill text-warning"></i> {{ number_format($d->reviews_avg_rating, 1) }}</div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="empty">No doctors yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><span>New Patients</span><a class="small" href="{{ route('admin.patients') }}">View all</a></div>
                <div class="card-body pt-2">
                    @forelse($newPatients as $p)
                        <div class="list-row"><span class="avatar-sm">{{ $p->initials }}</span>
                            <div class="flex-grow-1"><a class="fw-semibold text-decoration-none" href="{{ route('admin.patients.show', $p) }}">{{ $p->name }}</a>
                                <div class="small text-secondary">Joined {{ $p->created_at->format('d M Y') }}</div></div>
                            <span class="tag tag-indigo">{{ $p->appointments_count }} {{ $p->appointments_count == 1 ? 'visit' : 'visits' }}</span>
                        </div>
                    @empty
                        <div class="empty">No patients yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><span>Consultation by Department</span></div>
                <div class="card-body">
                    @php $maxDept = max(1, (int) ($departments->max('total') ?? 1)); @endphp
                    @forelse($departments as $dp)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small"><span class="fw-semibold">{{ $dp->name }}</span><span class="text-secondary">{{ $dp->total }}</span></div>
                            <div class="progress" style="height:8px"><div class="progress-bar" style="width:{{ round($dp->total / $maxDept * 100) }}%"></div></div>
                        </div>
                    @empty
                        <div class="empty">No consultations yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><span>Income by Department</span></div>
                <div class="card-body pt-2">
                    @forelse($income as $i)
                        <div class="list-row">
                            <div class="flex-grow-1"><div class="fw-semibold">{{ $i->name }}</div>
                                <div class="small text-secondary">{{ $i->visits }} {{ $i->visits == 1 ? 'visit' : 'visits' }}</div></div>
                            <b>&#8377;{{ number_format($i->amount) }}</b>
                        </div>
                    @empty
                        <div class="empty">Income appears once visits are completed.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><span>Patient Feedback</span><a class="small" href="{{ route('admin.reviews') }}">All reviews</a></div>
                <div class="card-body pt-1">
                    @if ($rating['count'])
                        @include('partials.rating-summary', ['summary' => $rating])
                        @foreach ($latestReviews as $rv)
                            @include('partials.review-item', ['rv' => $rv, 'showDoctor' => true])
                        @endforeach
                    @else
                        <div class="rv-empty"><i class="bi bi-star"></i>Ratings appear once patients review completed visits.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if ($topRated->count())
        <div class="card mb-3">
            <div class="card-header"><span>Top Rated Doctors</span></div>
            <div class="card-body pt-2">
                <div class="row g-3">
                    @foreach ($topRated as $d)
                        <div class="col-sm-6 col-xl-3">
                            <div class="list-row">@include('partials.doc-photo-sm', ['d' => $d])
                                <div class="flex-grow-1"><a class="fw-semibold text-decoration-none" href="{{ route('admin.doctors.show', $d) }}">{{ $d->user->name }}</a>
                                    <div class="small text-secondary">{{ $d->speciality->name }}</div></div>
                                <div class="text-end small"><i class="bi bi-star-fill text-warning"></i> {{ number_format($d->reviews_avg_rating, 1) }}
                                    <div class="text-secondary">({{ $d->reviews_count }})</div></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header"><span>Latest Appointments</span><a class="small" href="{{ route('admin.appointments') }}">View all</a></div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr><th>Patient</th><th>Doctor</th><th>Date &amp; Time</th><th>Fees</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse($latest as $a)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2"><span class="avatar-sm">{{ $a->patient->initials }}</span>
                                    <div>{{ $a->patient->name }}<div class="small text-secondary">{{ $a->patient->phone ?: $a->patient->email }}</div></div>
                                </div>
                            </td>
                            <td>{{ $a->doctor->user->name }}</td>
                            <td>{{ $a->date->format('d M Y') }}, {{ \Carbon\Carbon::parse($a->time)->format('g:i A') }}</td>
                            <td>&#8377;{{ number_format($a->fee) }}</td>
                            <td><span class="status status-{{ $a->status }}">{{ $a->statusLabel() }}</span></td>
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