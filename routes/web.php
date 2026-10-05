<?php

use App\Http\Controllers\{AdminController, AuthController, ConsultController, DoctorPanelController, DoctorRegistrationController, PatientProfileController, PaymentController, PublicController, ReviewController, EmailVerificationController, PasswordResetController, PrescriptionController};
use App\Http\Middleware\EnsureDoctorVerified;
use App\Http\Middleware\RoleMiddleware as R;
use Illuminate\Support\Facades\Route;

// Keeps the session and the CSRF token fresh while a page stays open (script in layouts/head.blade.php)
Route::get('/session-ping', fn () => response()->json(['token' => csrf_token(), 'auth' => auth()->check()])->header('Cache-Control', 'no-store'))
    ->middleware('throttle:30,1')->name('session.ping');

// Public website
Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/doctors', [PublicController::class, 'doctors'])->name('doctors');
Route::get('/doctors/{doctor}', [PublicController::class, 'show'])->name('doctors.show');

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1'); // IP-wide cap; the per-account limit is inside AuthController::login
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/register/doctor', [DoctorRegistrationController::class, 'create'])->name('register.doctor');
    Route::post('/register/doctor', [DoctorRegistrationController::class, 'store'])->name('register.doctor.store');

    // Forgot password with an email OTP (patients, doctors and admins)
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendCode'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1')->name('password.update');
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Email verification with a 6-digit OTP (enter code + resend code)
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::post('/email/verify', [EmailVerificationController::class, 'verify'])->middleware('throttle:10,1')->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])->middleware('throttle:6,1')->name('verification.send');
});

// Prescription: view + PDF download for the doctor of the visit, the patient and the admin (checked inside the controller)
Route::middleware('auth')->group(function () {
    Route::get('/prescriptions/{appointment}', [PrescriptionController::class, 'show'])->name('prescription.show');
    Route::get('/prescriptions/{appointment}/pdf', [PrescriptionController::class, 'pdf'])->name('prescription.pdf');
});

// Video room of an Online appointment: only the patient and the doctor of that appointment (checked inside the controller)
Route::middleware('auth')->prefix('consult/{appointment}')->name('consult.')->group(function () {
    Route::get('/', [ConsultController::class, 'show'])->name('show');
    Route::get('/state', [ConsultController::class, 'state'])->middleware('throttle:60,1')->name('state');
    Route::post('/start', [ConsultController::class, 'start'])->name('start');
    Route::post('/complete', [ConsultController::class, 'complete'])->name('complete');
});

// Razorpay webhook (no login, no CSRF: it is checked by its signature)
Route::post('/razorpay/webhook', [PaymentController::class, 'webhook'])->middleware('throttle:120,1')->name('razorpay.webhook');

// Patient
Route::middleware([R::class . ':patient'])->group(function () {
    // Online payment of the fee
    Route::get('/my-appointments/{appointment}/pay', [PaymentController::class, 'show'])->name('patient.pay');
    Route::post('/my-appointments/{appointment}/pay', [PaymentController::class, 'verify'])->name('patient.pay.verify');
    Route::get('/dashboard', [PublicController::class, 'patientDashboard'])->name('patient.dashboard');
    Route::post('/doctors/{doctor}/book', [PublicController::class, 'book'])->name('book');
    Route::get('/my-appointments', [PublicController::class, 'myAppointments'])->name('patient.appointments');
    Route::get('/my-transactions', [PatientProfileController::class, 'transactions'])->name('patient.transactions');
    Route::get('/my-profile', [PatientProfileController::class, 'show'])->name('patient.profile');
    Route::get('/my-profile/edit', [PatientProfileController::class, 'edit'])->name('patient.profile.edit');
    Route::put('/my-profile', [PatientProfileController::class, 'update'])->name('patient.profile.update');
    Route::get('/my-transactions/{transaction}/receipt', [PatientProfileController::class, 'receipt'])->name('patient.receipt');
    Route::post('/my-appointments/{appointment}/cancel', [PublicController::class, 'cancel'])->name('patient.cancel');
    Route::get('/my-appointments/{appointment}/reschedule', [PublicController::class, 'rescheduleForm'])->name('patient.reschedule');
    Route::post('/my-appointments/{appointment}/reschedule', [PublicController::class, 'reschedule'])->name('patient.reschedule.update');
    // Ratings: pop-up submit, "don't ask again", and the poll that opens the pop-up after a visit is completed
    Route::get('/my-reviews/pending', [ReviewController::class, 'pending'])->name('patient.review.pending');
    Route::post('/my-appointments/{appointment}/review', [ReviewController::class, 'store'])->name('patient.review.store');
    Route::post('/my-appointments/{appointment}/review/skip', [ReviewController::class, 'skip'])->name('patient.review.skip');
});

// Doctor panel
Route::middleware([R::class . ':doctor'])->prefix('doctor')->name('doctor.')->group(function () {
    // email OTP page (before the dashboard opens)
    Route::get('/verification', [DoctorRegistrationController::class, 'status'])->name('verification');

    // dashboard opens once the email is verified; the doctor goes live after admin eKYC approval
    Route::middleware([EnsureDoctorVerified::class])->group(function () {
        Route::get('/dashboard', [DoctorPanelController::class, 'dashboard'])->name('dashboard');
        Route::get('/kyc', [DoctorRegistrationController::class, 'kyc'])->name('kyc');
        Route::post('/kyc', [DoctorRegistrationController::class, 'submitKyc'])->name('kyc.submit');
        Route::get('/appointments', [DoctorPanelController::class, 'appointments'])->name('appointments');
        Route::get('/transactions', [DoctorPanelController::class, 'transactions'])->name('transactions');
        Route::get('/patients', [DoctorPanelController::class, 'patients'])->name('patients');
        Route::get('/reviews', [ReviewController::class, 'doctorIndex'])->name('reviews');
        Route::patch('/appointments/{appointment}', [DoctorPanelController::class, 'update'])->name('appointments.update');
        Route::get('/appointments/{appointment}/prescription', [PrescriptionController::class, 'edit'])->name('prescription.edit');
        Route::put('/appointments/{appointment}/prescription', [PrescriptionController::class, 'save'])->name('prescription.save');
        Route::get('/leaves', [DoctorPanelController::class, 'leaves'])->name('leaves');
        Route::post('/leaves', [DoctorPanelController::class, 'storeLeave'])->name('leaves.store');
        Route::delete('/leaves/{leave}', [DoctorPanelController::class, 'destroyLeave'])->name('leaves.destroy');
        Route::get('/profile', [DoctorPanelController::class, 'editProfile'])->name('profile');
        Route::put('/profile', [DoctorPanelController::class, 'updateProfile'])->name('profile.update');
    });
});

// Admin panel
Route::middleware([R::class . ':admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/doctors', [AdminController::class, 'doctors'])->name('doctors');
    Route::get('/doctors/create', [AdminController::class, 'createDoctor'])->name('doctors.create');
    Route::get('/doctors/verifications', [AdminController::class, 'verifications'])->name('doctors.verifications');
    Route::get('/doctors/{doctor}/verify', [AdminController::class, 'verifyDoctor'])->whereNumber('doctor')->name('doctors.verify');
    Route::post('/doctors/{doctor}/approve', [AdminController::class, 'approveDoctor'])->whereNumber('doctor')->name('doctors.approve');
    Route::post('/doctors/{doctor}/reject', [AdminController::class, 'rejectDoctor'])->whereNumber('doctor')->name('doctors.reject');
    Route::get('/doctors/{doctor}/document/{type}', [AdminController::class, 'document'])->whereNumber('doctor')->name('doctors.document');
    Route::get('/doctors/schedule', [AdminController::class, 'schedule'])->name('doctors.schedule');
    Route::get('/doctors/{doctor}', [AdminController::class, 'showDoctor'])->whereNumber('doctor')->name('doctors.show');
    Route::post('/doctors', [AdminController::class, 'storeDoctor'])->name('doctors.store');
    Route::get('/doctors/{doctor}/edit', [AdminController::class, 'editDoctor'])->name('doctors.edit');
    Route::put('/doctors/{doctor}', [AdminController::class, 'updateDoctor'])->name('doctors.update');
    Route::patch('/doctors/{doctor}/toggle', [AdminController::class, 'toggleDoctor'])->name('doctors.toggle');
    Route::delete('/doctors/{doctor}', [AdminController::class, 'destroyDoctor'])->name('doctors.destroy');

    Route::get('/patients', [AdminController::class, 'patients'])->name('patients');
    Route::get('/patients/{patient}', [AdminController::class, 'showPatient'])->name('patients.show');
    Route::delete('/patients/{patient}', [AdminController::class, 'destroyPatient'])->name('patients.destroy');

    Route::get('/transactions', [AdminController::class, 'transactions'])->name('transactions');
    Route::get('/appointments', [AdminController::class, 'appointments'])->name('appointments');
    Route::patch('/appointments/{appointment}', [AdminController::class, 'updateAppointment'])->name('appointments.update');

    Route::get('/reviews', [ReviewController::class, 'adminIndex'])->name('reviews');
    Route::patch('/reviews/{review}/toggle', [ReviewController::class, 'toggle'])->name('reviews.toggle');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
});