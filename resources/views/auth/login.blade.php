@extends('layouts.auth')
@section('title', 'Log in')
@section('headline', 'Welcome back. Your appointments are waiting.')
@section('content')
    <h1 class="fw-bold mb-1" style="font-size:1.7rem">
        Log in</h1>
    <p class="text-secondary mb-4">Patients, doctors and admins all sign in here.</p>
    @if (session('status'))
        <div class="alert alert-success py-2 small"><i class="bi bi-check-circle-fill"></i> {{ session('status') }}</div>
    @endif
    <form method="post" action="{{ route('login') }}" data-loading>@csrf
        <div class="mc-field"><i class="bi bi-envelope lead-ic"></i>
            <input type="email" name="email" value="{{ old('email') }}" placeholder="Email address"
                class="form-control @error('email') is-invalid @enderror" required autofocus>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="mc-field"><i class="bi bi-lock lead-ic"></i>
            <input type="password" name="password" placeholder="Password" class="form-control" required>
            <button type="button" class="eye" aria-label="Show password"><i class="bi bi-eye"></i></button>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="form-check mb-0"><input type="checkbox" name="remember" class="form-check-input"
                    id="r"><label for="r" class="form-check-label small">Keep me logged in</label></div>
            <a href="{{ route('password.request') }}" class="small fw-semibold text-decoration-none">Forgot password?</a>
        </div>
        <button type="submit" class="btn btn-primary w-100 mc-btn py-2">Log in</button>
    </form>
    <p class="small text-secondary mt-4 mb-0 text-center">New patient? <a
            href="{{ route('register', array_filter(['redirect' => request('redirect')])) }}"
            class="fw-semibold text-decoration-none">Create an account</a><br>Are you a doctor? <a
            href="{{ route('register.doctor') }}" class="fw-semibold text-decoration-none">Register as a doctor</a></p>
@endsection
