<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Doctor dashboard opens as soon as the email OTP is verified. Going live on the website needs the admin's eKYC approval. */
class EnsureDoctorVerified
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user()?->doctor || ! $request->user()->hasVerifiedEmail()) {
            return redirect()->route('doctor.verification');
        }
        return $next($request);
    }
}
