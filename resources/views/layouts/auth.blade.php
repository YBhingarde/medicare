<!doctype html>
<html lang="en">
<head>@include('layouts.head')<title>@yield('title', 'MediCare')</title></head>
<body>
<div class="mc-auth">
  <aside class="mc-auth-side">
    <div class="mc-blob b1"></div><div class="mc-blob b2"></div><div class="mc-blob b3"></div>
    <a class="mc-brand text-white position-relative" href="{{ route('home') }}"><i class="bi bi-heart-pulse-fill" style="background:#fff;color:#2563eb"></i> MediCare</a>
    <div class="position-relative">
      <h2 class="mc-up">@yield('headline', 'Your health, booked in a minute.')</h2>
      <p class="mc-up mb-4" style="--d:100ms;color:rgba(255,255,255,.85)">Find trusted doctors, see real availability and book a slot without calling anyone.</p>
      <div class="perk mc-up" style="--d:200ms"><i class="bi bi-calendar2-check"></i><div><b>Live slots</b><div class="small opacity-75">Pick a time that suits you</div></div></div>
      <div class="perk mc-up" style="--d:300ms"><i class="bi bi-patch-check"></i><div><b>Verified doctors</b><div class="small opacity-75">Licence, education and awards shown</div></div></div>
      <div class="perk mc-up" style="--d:400ms"><i class="bi bi-clock-history"></i><div><b>Track appointments</b><div class="small opacity-75">Confirm, cancel or review anytime</div></div></div>
    </div>
    <div class="small position-relative" style="color:rgba(255,255,255,.7)">&copy; {{ date('Y') }} MediCare</div>
  </aside>
  <main class="mc-auth-main">
    <div class="mc-auth-box mc-up">
      <a class="mc-brand d-lg-none mb-4" href="{{ route('home') }}"><i class="bi bi-heart-pulse-fill"></i> MediCare</a>
      @yield('content')
    </div>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/site.js') }}"></script>
</body></html>
