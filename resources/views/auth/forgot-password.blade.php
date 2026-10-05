@extends('layouts.auth')
@section('title', 'Forgot password')
@section('headline', 'No problem. We will help you get back in.')
@section('content')
    <h1 class="fw-bold mb-1" style="font-size:1.7rem">
        Forgot your password?</h1>
    <p class="text-secondary mb-4">Enter the email you signed up with. We will send a 6-digit code to reset your password.
        Works for patients, doctors and admins.</p>
    <form method="post" action="{{ route('password.email') }}" data-loading>@csrf
        <div class="mc-field"><i class="bi bi-envelope lead-ic"></i>
            <input type="email" name="email" value="{{ old('email') }}" placeholder="Email address"
                class="form-control @error('email') is-invalid @enderror" required autofocus>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <button type="submit" class="btn btn-primary w-100 mc-btn py-2">Send code</button>
    </form>
    <p class="small text-secondary mt-4 mb-0 text-center"><a href="{{ route('login') }}"
            class="fw-semibold text-decoration-none"><i class="bi bi-arrow-left"></i> Back to log in</a></p>
@endsection
