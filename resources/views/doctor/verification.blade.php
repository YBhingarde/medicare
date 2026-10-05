@extends('layouts.auth')
@section('title', 'Verify your email')
@section('headline', 'Verify your email to open your dashboard.')
@section('content')
    <style>
        .mc-auth-box {
            max-width: 560px
        }

        .mc-auth .mc-sec {
            font-weight: 700;
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--pc);
            margin: 1.2rem 0 .8rem
        }
    </style>
    @if (session('ok'))
        <div class="alert alert-success py-2 small">{{ session('ok') }}</div>
    @endif
            <div class="border rounded-3 p-3 mb-4" style="background:#eef0ff;border-color:#d5d8fb!important">
            <div class="d-flex gap-3 align-items-start">
                <div class="flex-none"
                    style="width:44px;height:44px;border-radius:12px;background:#2563eb;color:#fff;display:grid;place-items:center;font-size:1.2rem">
                    <i class="bi bi-envelope-paper-fill"></i></div>
                <div class="flex-grow-1">
                    <b>Verify your email</b>
                    <div class="small text-secondary mt-1">We emailed a 6-digit code to <b>{{ auth()->user()->email }}</b>. Enter
                        it below. The code is valid for 10 minutes. Your dashboard opens right after.</div>
                    <form method="post" action="{{ route('verification.verify') }}" class="d-flex flex-wrap gap-2 mt-3"
                        data-loading>@csrf
                        <input type="text" name="otp" inputmode="numeric" pattern="[0-9]*" maxlength="6"
                            autocomplete="one-time-code" placeholder="000000" required autofocus
                            class="form-control text-center fw-bold @error('otp') is-invalid @enderror"
                            style="max-width:170px;font-size:1.35rem;letter-spacing:.45em;padding-left:.9em">
                        <button type="submit" class="btn btn-primary mc-btn px-4"><i class="bi bi-check2-circle"></i>
                            Verify</button>
                    </form>
                    @error('otp')
                        <div class="small text-danger mt-2">{{ $message }}</div>
                    @enderror
                    @error('mail')
                        <div class="small text-danger mt-2">{{ $message }}</div>
                    @enderror
                    <form method="post" action="{{ route('verification.send') }}" class="mt-2">@csrf
                        <span class="small text-secondary">Did not get it? Check spam, or</span>
                        <button type="submit"
                            class="btn btn-link btn-sm p-0 align-baseline text-decoration-none fw-semibold">resend code</button>
                    </form>
                </div>
            </div>
        </div>
    <form method="post" action="{{ route('logout') }}" class="text-center mt-4">@csrf<button
            class="btn btn-link text-decoration-none small">Log out</button></form>
@endsection
