<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Otp;
use Illuminate\Http\Request;

/** Email verification with a 6-digit OTP. Used right now for doctors, works for any role. */
class EmailVerificationController extends Controller
{
    /** Anything that sends an unverified user "to verify" lands on their own page, where the OTP box is. */
    public function notice(Request $r)
    {
        return redirect($this->after($r->user()));
    }

    /** Doctor types the 6-digit code from the email. */
    public function verify(Request $r)
    {
        $u = $r->user();
        if ($u->hasVerifiedEmail()) return redirect($this->after($u));

        $d = $r->validate(['otp' => ['required', 'digits:6']], [
            'otp.required' => 'Enter the 6-digit code from your email.',
            'otp.digits' => 'The code has 6 digits.',
        ]);

        $result = Otp::verify($u->email, Otp::VERIFY, $d['otp']);
        if ($result !== 'ok') return back()->withErrors(['otp' => Otp::message($result)]);

        $u->markEmailAsVerified();
        return redirect($this->after($u))->with('ok', 'Your email address is verified. Thank you!');
    }

    /** "Resend code" button. */
    public function send(Request $r)
    {
        $u = $r->user();
        if ($u->hasVerifiedEmail()) return redirect($this->after($u));

        $res = Otp::send($u->email, Otp::VERIFY, $u->name);
        if ($res === 'wait') return back()->withErrors(['mail' => 'Please wait a minute before asking for a new code.']);
        if ($res === 'failed') return back()->withErrors(['mail' => 'We could not send the email right now. Please try again in a few minutes.' . (config('app.debug') && Otp::$lastError ? ' [Mail error: ' . \Illuminate\Support\Str::limit(Otp::$lastError, 300) . ']' : '')]);
        return back()->with('ok', 'A new 6-digit code has been sent to ' . $u->email . '.');
    }

    private function after(User $u): string
    {
        return match ($u->role) {
            'doctor' => $u->hasVerifiedEmail() ? route('doctor.dashboard') : route('doctor.verification'),
            'admin' => route('admin.dashboard'),
            default => route('patient.dashboard'),
        };
    }
}
