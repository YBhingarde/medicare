@extends('layouts.auth')
@section('title','Reset password')
@section('headline','Enter the code and choose a new password.')
@section('content')
<h1 class="fw-bold mb-1" style="font-size:1.7rem">Reset your password</h1>
<p class="text-secondary mb-3">Enter the 6-digit code we emailed you and choose a new password (at least 8 characters).</p>
@if(session('status'))<div class="alert alert-success py-2 small"><i class="bi bi-check-circle-fill"></i> {{ session('status') }}</div>@endif
<form method="post" action="{{ route('password.update') }}" data-loading>@csrf
  <div class="mc-field"><i class="bi bi-envelope lead-ic"></i>
    <input type="email" name="email" value="{{ old('email', $email) }}" placeholder="Email address" class="form-control @error('email') is-invalid @enderror" required>
    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
  <div class="mc-field"><i class="bi bi-shield-lock lead-ic"></i>
    <input type="text" name="otp" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code" placeholder="6-digit code" class="form-control @error('otp') is-invalid @enderror" required autofocus>
    @error('otp')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
  <div class="mc-field"><i class="bi bi-lock lead-ic"></i>
    <input type="password" name="password" placeholder="New password" class="form-control @error('password') is-invalid @enderror" minlength="8" required>
    <button type="button" class="eye" aria-label="Show password"><i class="bi bi-eye"></i></button>
    @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
  <div class="mc-field"><i class="bi bi-lock-fill lead-ic"></i>
    <input type="password" name="password_confirmation" placeholder="Confirm new password" class="form-control" minlength="8" required></div>
  <button type="submit" class="btn btn-primary w-100 mc-btn py-2">Change password</button>
</form>
<form method="post" action="{{ route('password.email') }}" class="text-center mt-3">@csrf
  <input type="hidden" name="email" value="{{ old('email', $email) }}">
  <span class="small text-secondary">Did not get the code?</span>
  <button type="submit" class="btn btn-link btn-sm p-0 align-baseline text-decoration-none fw-semibold">Send again</button>
</form>
<p class="small text-secondary mt-3 mb-0 text-center"><a href="{{ route('login') }}" class="fw-semibold text-decoration-none"><i class="bi bi-arrow-left"></i> Back to log in</a></p>
@endsection
