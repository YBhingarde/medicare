<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Razorpay calls this URL from its own servers, so it cannot carry a CSRF token (it is protected by the webhook signature)
        $middleware->validateCsrfTokens(except: ['razorpay/webhook']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // "419 Page Expired" (session or CSRF token ran out): never show the dead error page, send the user somewhere useful
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() !== 419) return null; // every other error keeps its normal page
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your session expired. Please refresh the page.'], 419);
            }
            // logging out with an expired session: the user is already logged out
            if ($request->routeIs('logout')) return redirect()->route('home');
            if (! auth()->check()) {
                return redirect()->route('login')->withErrors(['email' => 'Your session expired. Please log in again.']);
            }
            return back()->withErrors(['session' => 'This page was open for too long. Please try again.'])
                ->withInput($request->except('_token', 'password', 'password_confirmation'));
        });
    })->create();
