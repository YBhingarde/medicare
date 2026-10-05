@extends('layouts.auth')
@section('title', 'Sign up')
@section('headline', 'Create your account and book your first visit.')
@section('content')
    <h1 class="fw-bold mb-1" style="font-size:1.7rem">
        Create your account</h1>
    <p class="text-secondary mb-4">Patient sign-up takes less than a minute.</p>
    <form method="post" action="{{ route('register') }}" data-loading>@csrf
        @foreach ([['name', 'Full name', 'text', 'person'], ['email', 'Email address', 'email', 'envelope'], ['phone', 'Mobile number (optional)', 'text', 'phone'], ['password', 'Password', 'password', 'lock'], ['password_confirmation', 'Confirm password', 'password', 'shield-lock']] as [$n, $l, $t, $ic])
            <div class="mc-field"><i class="bi bi-{{ $ic }} lead-ic"></i>
                <input type="{{ $t }}" name="{{ $n }}" placeholder="{{ $l }}"
                    value="{{ $t === 'password' ? '' : old($n) }}" class="form-control @error($n) is-invalid @enderror"
                    @required($n !== 'phone')>
                @if ($t === 'password')
                    <button type="button" class="eye" aria-label="Show password"><i class="bi bi-eye"></i></button>
                @endif
                @error($n)
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        @endforeach
        <button type="submit" class="btn btn-primary w-100 mc-btn py-2">Create account</button>
    </form>
    <p class="small text-secondary mt-4 mb-0 text-center">Already registered? <a
            href="{{ route('login', array_filter(['redirect' => request('redirect')])) }}"
            class="fw-semibold text-decoration-none">Log in</a><br>Are you a doctor? <a
            href="{{ route('register.doctor') }}" class="fw-semibold text-decoration-none">Register as a doctor</a></p>
@endsection
