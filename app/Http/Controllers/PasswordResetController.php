<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Otp;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Forgot password with a 6-digit email OTP, for every role (patient, doctor, admin). */
class PasswordResetController extends Controller
{
    /** Step 1: ask for the email. */
    public function showForgot() { return view('auth.forgot-password'); }

    public function sendCode(Request $r)
    {
        $d = $r->validate(['email' => 'required|email']);
        $user = User::where('email', $d['email'])->first();
        $debug = config('app.debug');
        if (! $user) {
            // Normally the answer is identical for unknown emails (so nobody can probe accounts).
            // While APP_DEBUG=true we say it plainly, because a mistyped email is the most common reason "no code arrived".
            if ($debug) return back()->withErrors(['email' => 'No account exists with this email (shown only while APP_DEBUG=true).'])->onlyInput('email');
        } else {
            $res = Otp::send($user->email, Otp::RESET, $user->name);
            if ($res === 'failed') {
                $msg = 'We could not send the email right now. Please try again later.';
                if ($debug && Otp::$lastError) $msg .= ' [Mail error: ' . Str::limit(Otp::$lastError, 300) . ']';
                return back()->withErrors(['email' => $msg])->onlyInput('email');
            }
            if ($res === 'wait') {
                return redirect()->route('password.reset', ['email' => $d['email']])
                    ->with('status', 'A code was already sent less than a minute ago. Check your inbox and spam folder, or go back and request a new one after a minute.');
            }
        }
        return redirect()->route('password.reset', ['email' => $d['email']])
            ->with('status', 'If an account exists for ' . $d['email'] . ', we have sent a 6-digit code. It is valid for ' . Otp::TTL_MINUTES . ' minutes.');
    }

    /** Step 2: code + new password. */
    public function showReset(Request $r)
    {
        return view('auth.reset-password', ['email' => $r->query('email')]);
    }

    public function reset(Request $r)
    {
        $d = $r->validate([
            'email' => 'required|email',
            'otp' => ['required', 'digits:6'],
            'password' => 'required|string|min:8|confirmed',
        ], ['otp.required' => 'Enter the 6-digit code from your email.', 'otp.digits' => 'The code has 6 digits.']);

        $user = User::where('email', $d['email'])->first();
        $result = $user ? Otp::verify($user->email, Otp::RESET, $d['otp']) : 'invalid';
        if ($result !== 'ok') {
            return back()->withErrors(['otp' => $user ? Otp::message($result) : Otp::message('invalid')])->onlyInput('email');
        }

        $user->forceFill(['password' => $d['password'], 'remember_token' => Str::random(60)])->save();
        if (! $user->hasVerifiedEmail()) $user->markEmailAsVerified(); // they just proved they own this mailbox
        event(new PasswordReset($user));

        return redirect()->route('login')->with('status', 'Your password has been changed. Please log in with the new password.');
    }
}
