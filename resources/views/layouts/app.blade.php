<!doctype html>
<html lang="en">
<head>@include('layouts.head')<title>@yield('title', 'MediCare')</title></head>
<body>
@php $full = $__env->hasSection('full'); $wrap = $full ? 'mc-wrap' : 'container'; @endphp
<nav class="navbar navbar-expand-lg mc-nav sticky-top">
  <div class="{{ $wrap }} mc-nav-in">
    <a class="mc-brand" href="{{ route('home') }}"><i class="bi bi-heart-pulse-fill"></i><span>MediCare</span></a>

    @auth
      @php
        $u = auth()->user();
        $dash = match ($u->role) { 'admin' => route('admin.dashboard'), 'doctor' => route('doctor.dashboard'), default => route('patient.dashboard') };
        $profileUrl = match ($u->role) { 'patient' => route('patient.profile'), 'doctor' => route('doctor.profile'), default => null };
      @endphp
      <div class="dropdown mc-um order-lg-last ms-auto ms-lg-0">
        <button class="mc-um-btn" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" aria-label="Account menu">
          @include('partials.user-avatar', ['u' => $u])
          <span class="mc-um-text">
            <span class="mc-um-name">{{ $u->name }}</span>
            <span class="mc-um-role">{{ ucfirst($u->role) }}</span>
          </span>
          <i class="bi bi-chevron-down mc-um-chev"></i>
        </button>
        <div class="dropdown-menu mc-um-menu">
          <div class="mc-um-head">
            @include('partials.user-avatar', ['u' => $u, 'cls' => 'lg'])
            <div class="mc-um-who">
              <b>{{ $u->name }}</b>
              <small>{{ $u->email }}</small>
              <span class="mc-um-badge">{{ ucfirst($u->role) }}</span>
            </div>
          </div>
          <a class="mc-um-item" href="{{ $dash }}"><i class="bi bi-speedometer2"></i> Dashboard</a>
          @if($profileUrl)<a class="mc-um-item" href="{{ $profileUrl }}"><i class="bi bi-person-circle"></i> My profile</a>@endif
          @if($u->role === 'patient')<a class="mc-um-item" href="{{ route('patient.appointments') }}"><i class="bi bi-calendar2-check"></i> My appointments</a>@endif
          <form method="post" action="{{ route('logout') }}">@csrf<button type="submit" class="mc-um-item out"><i class="bi bi-box-arrow-right"></i> Log out</button></form>
        </div>
      </div>
    @endauth

    <button class="navbar-toggler border-0 mc-toggler" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-label="Menu"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto ms-lg-3">
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Home</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('doctors*') ? 'active' : '' }}" href="{{ route('doctors') }}">Find doctors</a></li>
        @auth @if(auth()->user()->role==='patient')
          <li class="nav-item"><a class="nav-link {{ request()->routeIs('patient.appointments') ? 'active' : '' }}" href="{{ route('patient.appointments') }}">My appointments</a></li>
        @endif @endauth
      </ul>
      @guest
        <div class="mc-nav-act d-flex gap-2 align-items-center">
          <a class="btn btn-sm btn-outline-primary mc-btn" href="{{ route('register.doctor') }}">For doctors</a>
          <a class="btn btn-sm btn-light mc-btn" href="{{ route('login') }}">Log in</a>
          <a class="btn btn-sm btn-primary mc-btn" href="{{ route('register') }}">Sign up</a>
        </div>
      @endguest
    </div>
  </div>
</nav>
<main class="{{ $full ? 'mc-main-full' : 'container py-4' }}">
  @if(session('ok'))<div class="{{ $full ? 'mc-wrap pt-3' : '' }}"><div class="alert alert-success mc-up border-0 shadow-sm"><i class="bi bi-check-circle-fill me-1"></i> {{ session('ok') }}</div></div>@endif
  @yield('content')
</main>
<footer class="mc-footer mt-5">
  <div class="{{ $wrap }} py-5">
    <div class="row g-4">
      <div class="col-lg-5">
        <a class="mc-brand text-white" href="{{ route('home') }}"><i class="bi bi-heart-pulse-fill"></i> MediCare</a>
        <p class="mt-3 mb-0 mc-foot-txt">Find trusted, verified doctors, check their real availability and book your appointment in under a minute.</p>
      </div>
      <div class="col-6 col-lg-3">
        <h6>Quick links</h6>
        <ul class="list-unstyled mb-0">
          <li><a href="{{ route('home') }}">Home</a></li>
          <li><a href="{{ route('doctors') }}">Find doctors</a></li>
          @guest
            <li><a href="{{ route('register.doctor') }}">For doctors</a></li>
            <li><a href="{{ route('login') }}">Log in</a></li>
            <li><a href="{{ route('register') }}">Sign up</a></li>
          @endguest
        </ul>
      </div>
      <div class="col-6 col-lg-4">
        <h6>Popular specialities</h6>
        <ul class="list-unstyled mb-0">
          @foreach(\App\Models\Speciality::orderBy('name')->take(6)->get() as $fs)
            <li><a href="{{ route('doctors', ['speciality' => $fs->id]) }}">{{ $fs->name }}</a></li>
          @endforeach
        </ul>
      </div>
    </div>
    <div class="mc-foot-bar d-flex flex-wrap justify-content-between gap-2">
      <span>&copy; {{ date('Y') }} MediCare. Book trusted doctors near you.</span>
      <span>Made with <i class="bi bi-heart-fill mc-beat" style="color:#ff6b81"></i> for better healthcare</span>
    </div>
    <div class="mc-devby-wrap">
      <a class="mc-devby" href="https://hsdigitalmedia.com/" target="_blank" rel="noopener" aria-label="Developed by HS Digital Media">
        <span class="mc-devby-ico"><i class="bi bi-code-slash"></i></span>
        <span class="mc-devby-txt">Developed by <strong>HS Digital Media<sup>&reg;</sup></strong></span>
        <i class="bi bi-arrow-up-right mc-devby-arr"></i>
      </a>
    </div>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/site.js') }}"></script>
</body></html>