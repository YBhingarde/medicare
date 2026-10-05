<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin(Request $request) { $this->rememberReturn($request); return view('auth.login'); }
    public function showRegister(Request $request) { $this->rememberReturn($request); return view('auth.register'); }

    /** "Log in to book" sends ?redirect=/doctors/5?date=..; after login / sign-up the patient comes back to that doctor. */
    private function rememberReturn(Request $request): void
    {
        $to = (string) $request->query('redirect', '');
        // only a doctor page of this site is accepted (no open redirect)
        if ($to !== '' && preg_match('#^/doctors/\d+(\?[^\s]*)?$#', $to)) {
            session(['url.intended' => url($to)]);
        } else {
            session()->forget('url.intended');
        }
    }

    public function login(Request $request)
    {
        $cred = $request->validate(['email' => 'required|email', 'password' => 'required']);

        // brute-force protection: 5 wrong passwords per minute for the same e-mail + IP, then a short lock
        $key = 'login:' . Str::lower($cred['email']) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $wait = RateLimiter::availableIn($key);
            return back()->withErrors(['email' => "Too many login attempts. Please try again in {$wait} seconds."])->onlyInput('email');
        }

        if (! Auth::attempt($cred, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            return back()->withErrors(['email' => 'Email or password is incorrect.'])->onlyInput('email');
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        $user = Auth::user();
        if ($user->role !== 'patient') $request->session()->forget('url.intended'); // only patients return to a doctor page
        return redirect()->intended($this->home($user));
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|min:6|confirmed',
        ]);
        $user = User::create($data + ['role' => 'patient']);
        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->intended($this->home($user));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }

    private function home(User $u): string
    {
        return match ($u->role) {
            'admin' => route('admin.dashboard'),
            'doctor' => route('doctor.dashboard'),
            default => route('patient.dashboard'),
        };
    }
}
