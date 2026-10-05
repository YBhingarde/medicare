<!doctype html>
<html lang="en">

<head>@include('layouts.head')<title>@yield('title', 'Dashboard') - MediCare</title>
    <link href="{{ asset('css/dashboard.css') }}" rel="stylesheet">
    <link href="{{ asset('css/dashboard-pro.css') }}" rel="stylesheet">
    <style>
        .dash .status-expired {
            background: #eef0f4;
            color: #6b7280;
            border-color: #d9dde5
        }

        .dash .status-expired::before {
            background: #9ca3af !important
        }
    </style>
    <script>
        try {
            if (localStorage.getItem('mc-theme') === 'dark') document.documentElement.setAttribute('data-theme', 'dark')
        } catch (e) {}
    </script>
</head>

<body class="dash role-{{ auth()->user()->role }}">
    @php
        $user = auth()->user();
        $role = $user->role;
        $menus =
            [
                'admin' => [
                    ['admin.dashboard', 'Dashboard', 'grid-1x2', 'admin.dashboard'],
                    ['admin.doctors', 'Doctors', 'person-badge', 'admin.doctors*'],
                    ['admin.patients', 'Patients', 'people', 'admin.patients*'],
                    ['admin.appointments', 'Appointments', 'calendar2-check', 'admin.appointments*'],
                    ['admin.transactions', 'Transactions', 'receipt', 'admin.transactions*'],
                    ['admin.reviews', 'Reviews', 'star', 'admin.reviews*'],
                ],
                'doctor' => [
                    ['doctor.dashboard', 'Dashboard', 'grid-1x2', 'doctor.dashboard'],
                    ['doctor.appointments', 'Appointments', 'calendar2-check', 'doctor.appointments*'],
                    ['doctor.transactions', 'Transactions', 'receipt', 'doctor.transactions*'],
                    ['doctor.patients', 'My Patients', 'people', 'doctor.patients*'],
                    ['doctor.reviews', 'Reviews', 'star', 'doctor.reviews*'],
                    ['doctor.leaves', 'My Leaves', 'calendar-x', 'doctor.leaves*'],
                    ['doctor.kyc', 'eKYC Verification', 'shield-check', 'doctor.kyc*'],
                    ['doctor.profile', 'My Profile', 'person-gear', 'doctor.profile*'],
                ],
                'patient' => [
                    ['patient.dashboard', 'Dashboard', 'grid-1x2', 'patient.dashboard'],
                    ['patient.profile', 'My Profile', 'person-circle', 'patient.profile*'],
                    ['patient.appointments', 'My Appointments', 'calendar2-check', 'patient.appointments*'],
                    ['patient.transactions', 'Transactions', 'receipt', 'patient.transactions*'],
                    ['doctors', 'Find Doctors', 'search', 'doctors*'],
                ],
            ][$role] ?? [];
        $searchRoute =
            ['admin' => 'admin.patients', 'doctor' => 'doctor.patients', 'patient' => 'doctors'][$role] ?? 'home';
    @endphp
    <aside class="side">
        <a class="brand" href="{{ route('home') }}"><i class="bi bi-heart-pulse-fill"></i> MediCare</a>
        <div class="menu-label">Main Menu</div>
        @foreach ($menus as [$r, $label, $icon, $match])
            @if ($role === 'admin' && $r === 'admin.doctors')
                @php
                    $open = request()->routeIs('admin.doctors*');
                    $firstDoc = \App\Models\Doctor::latest('id')->value('id');
                @endphp
                <a class="mi mi-group {{ $open ? 'active' : '' }}" data-bs-toggle="collapse" href="#menuDoctors"
                    role="button" aria-expanded="{{ $open ? 'true' : 'false' }}"><i class="bi bi-{{ $icon }}"></i>
                    {{ $label }} <i class="bi bi-chevron-down ms-auto chev"></i></a>
                <div class="collapse sub {{ $open ? 'show' : '' }}" id="menuDoctors">
                    <a class="{{ request()->routeIs('admin.doctors', 'admin.doctors.edit') ? 'on' : '' }}"
                        href="{{ route('admin.doctors') }}">Doctors</a>
                    <a class="{{ request()->routeIs('admin.doctors.show') ? 'on' : '' }}"
                        href="{{ $firstDoc ? route('admin.doctors.show', $firstDoc) : route('admin.doctors') }}">Doctor
                        Details</a>
                    <a class="{{ request()->routeIs('admin.doctors.create') ? 'on' : '' }}"
                        href="{{ route('admin.doctors.create') }}">Add Doctor</a>
                    <a class="{{ request()->routeIs('admin.doctors.schedule') ? 'on' : '' }}"
                        href="{{ route('admin.doctors.schedule') }}">Doctor Schedule</a>
                    @php $pendingDocs = \App\Models\Doctor::where('verification_status','pending')->count(); @endphp
                    <a class="{{ request()->routeIs('admin.doctors.verifications', 'admin.doctors.verify') ? 'on' : '' }}"
                        href="{{ route('admin.doctors.verifications') }}">Verification @if ($pendingDocs)
                            <span class="badge rounded-pill text-bg-danger ms-1">{{ $pendingDocs }}</span>
                        @endif
                    </a>
                </div>
            @else
                <a class="mi {{ request()->routeIs($match) ? 'active' : '' }}" href="{{ route($r) }}"><i
                        class="bi bi-{{ $icon }}"></i> {{ $label }}</a>
            @endif
        @endforeach
        <div class="menu-label mt-3">More</div>
        <a class="mi" href="{{ route('home') }}"><i class="bi bi-globe"></i> View website</a>
        <form method="post" action="{{ route('logout') }}">@csrf
            <button class="mi w-100 border-0 bg-transparent text-start d-flex align-items-center gap-2 p-0"
                style="padding:.55rem .7rem!important;color:var(--text);font-size:.83rem"><i
                    class="bi bi-box-arrow-right text-secondary"></i> Log out</button>
        </form>
        <div class="upgrade"><span
                class="role-pill">{{ $role }}</span><b>{{ $user->name }}</b><span>{{ $user->email }}</span>
        </div>
    </aside>
    <div class="main">
        <header class="topbar">
            <button class="icon-btn d-lg-none" id="sideToggle" aria-label="Menu"><i class="bi bi-list"></i></button>
            <form class="search" method="get" action="{{ route($searchRoute) }}"><i class="bi bi-search"></i><input
                    name="q" value="{{ request('q') }}" class="form-control"
                    placeholder="{{ $role === 'patient' ? 'Search doctors' : 'Search patients' }}"></form>
            <div class="ms-auto d-flex align-items-center gap-2">
                @if ($role === 'admin')
                    <a class="btn btn-primary btn-sm d-none d-sm-inline-block"
                        href="{{ route('admin.doctors.create') }}"><i class="bi bi-plus-lg"></i> Add Doctor</a>
                @endif
                @if ($role === 'patient')
                    <a class="btn btn-primary btn-sm" href="{{ route('doctors') }}"><i class="bi bi-plus-lg"></i> New
                        Appointment</a>
                @endif
                <button class="icon-btn" id="themeToggle" aria-label="Toggle dark mode"><i
                        class="bi bi-moon-stars"></i></button>
                @php $tbPhoto = $user->photo_url; @endphp
                @if ($role === 'patient')
                    <a class="text-decoration-none d-inline-flex" href="{{ route('patient.profile') }}"
                        title="{{ $user->name }} - My profile">@if ($tbPhoto)<img class="tb-photo" src="{{ $tbPhoto }}" alt="{{ $user->name }}">@else<span class="avatar-sm">{{ $user->initials }}</span>@endif</a>
                @elseif ($tbPhoto)<img class="tb-photo" src="{{ $tbPhoto }}" alt="{{ $user->name }}" title="{{ $user->name }}">
                @else<span class="avatar-sm" title="{{ $user->name }}">{{ $user->initials }}</span>
                @endif
            </div>
        </header>
        <div class="content">
            @if (session('ok'))
                <div class="alert alert-success py-2">{{ session('ok') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
            @endif
            @yield('content')
        </div>
    </div>
    @if ($role === 'patient')
        @include('partials.review-modal')
    @endif
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script src="{{ asset('js/dashboard.js') }}"></script>
    @stack('scripts')
</body>

</html>
