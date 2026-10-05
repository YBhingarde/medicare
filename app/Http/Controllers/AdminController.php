<?php
namespace App\Http\Controllers;

use App\Models\{Appointment, City, Doctor, Review, Speciality, Transaction, User};
use App\Support\DashboardStats;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};
use App\Http\Controllers\Concerns\ManagesDoctorProfile;

class AdminController extends Controller
{
    use ManagesDoctorProfile;

    public function dashboard()
    {
        $year = now()->year;
        $week = now()->subDays(7); $prevWeek = now()->subDays(14);
        $patientQ = fn () => User::where('role', 'patient');

        $chart = DashboardStats::monthly(Appointment::whereYear('date', $year)->get(['id', 'date', 'status']), $year);
        $status = DashboardStats::statusCounts(Appointment::get(['id', 'status']));

        // completed appointments earn the doctor's fee
        // revenue comes from the paid transactions (the fee frozen at booking), not from today's doctor fee
        $revenueQ = fn () => Transaction::join('doctors', 'doctors.id', '=', 'transactions.doctor_id')->where('transactions.status', 'paid');

        $departments = Appointment::join('doctors', 'doctors.id', '=', 'appointments.doctor_id')
            ->join('specialities', 'specialities.id', '=', 'doctors.speciality_id')
            ->whereNotIn('appointments.status', ['cancelled', 'expired'])
            ->groupBy('specialities.name')->select('specialities.name', DB::raw('count(*) as total'))
            ->orderByDesc('total')->take(4)->get();

        $income = $revenueQ()->join('specialities', 'specialities.id', '=', 'doctors.speciality_id')
            ->groupBy('specialities.name')->select('specialities.name', DB::raw('count(*) as visits'), DB::raw('sum(transactions.amount) as amount'))
            ->orderByDesc('amount')->take(5)->get();

        return view('admin.dashboard', [
            'cards' => [
                ['label' => 'Doctors', 'value' => Doctor::count(), 'icon' => 'person-badge', 'color' => 'indigo',
                    'delta' => DashboardStats::pct(Doctor::where('created_at', '>=', $week)->count(), Doctor::whereBetween('created_at', [$prevWeek, $week])->count()), 'sub' => Doctor::where('verification_status', 'pending')->count() . ' awaiting verification', 'url' => route('admin.doctors')],
                ['label' => 'Patients', 'value' => $patientQ()->count(), 'icon' => 'people', 'color' => 'red',
                    'delta' => DashboardStats::pct($patientQ()->where('created_at', '>=', $week)->count(), $patientQ()->whereBetween('created_at', [$prevWeek, $week])->count()), 'sub' => 'Registered patients', 'url' => route('admin.patients')],
                ['label' => 'Appointments', 'value' => Appointment::count(), 'icon' => 'calendar2-check', 'color' => 'blue',
                    'delta' => DashboardStats::pct(Appointment::where('created_at', '>=', $week)->count(), Appointment::whereBetween('created_at', [$prevWeek, $week])->count()), 'sub' => Appointment::whereDate('date', now())->count() . ' today', 'url' => route('admin.appointments')],
                ['label' => 'Revenue', 'value' => (int) $revenueQ()->sum('transactions.amount'), 'icon' => 'currency-rupee', 'color' => 'green', 'prefix' => '₹', 'sub' => 'From completed visits', 'url' => route('admin.transactions', ['status' => 'paid'])],
            ],
            'chart' => $chart, 'status' => $status, 'departments' => $departments, 'income' => $income,
            'popular' => Doctor::with('user', 'speciality')->withCount('appointments')->withAvg('reviews', 'rating')->withCount('reviews')->orderByDesc('appointments_count')->take(3)->get(),
            'rating' => Review::summary(),
            'topRated' => Doctor::with('user', 'speciality')->withAvg('reviews', 'rating')->withCount('reviews')->whereHas('reviews')->orderByDesc('reviews_avg_rating')->orderByDesc('reviews_count')->take(4)->get(),
            'latestReviews' => Review::with('doctor.user', 'patient')->where('is_visible', true)->latest()->take(4)->get(),
            'upcoming' => Appointment::with('doctor.user', 'patient')->whereDate('date', '>=', now()->toDateString())
                ->whereIn('status', Appointment::OPEN)->orderBy('date')->orderBy('time')->take(4)->get(),
            'newPatients' => $patientQ()->withCount('appointments')->latest()->take(5)->get(),
            'latest' => Appointment::with('doctor.user', 'patient')->latest()->take(8)->get(),
        ]);
    }

    /* ---------- Patients ---------- */
    public function patients(Request $r)
    {
        $q = User::where('role', 'patient')->withCount('appointments')->withMax('appointments', 'date');
        if ($r->filled('q')) $q->where(fn ($w) => $w->where('name', 'like', '%' . $r->q . '%')->orWhere('email', 'like', '%' . $r->q . '%')->orWhere('phone', 'like', '%' . $r->q . '%'));
        return view('admin.patients', ['patients' => $q->latest()->paginate(12)->withQueryString(), 'total' => User::where('role', 'patient')->count()]);
    }

    public function showPatient(User $patient)
    {
        abort_unless($patient->role === 'patient', 404);
        $appts = $patient->appointments()->with('doctor.user', 'doctor.speciality', 'transaction')->orderByDesc('date')->orderByDesc('time')->get();
        return view('admin.patient-show', [
            'patient' => $patient, 'appts' => $appts, 'status' => DashboardStats::statusCounts($appts),
            'spent' => (int) Transaction::where('patient_id', $patient->id)->where('status', 'paid')->sum('amount'),
        ]);
    }

    public function destroyPatient(User $patient)
    {
        abort_unless($patient->role === 'patient', 404);
        $patient->delete(); // cascades to appointments
        return redirect()->route('admin.patients')->with('ok', 'Patient deleted.');
    }

    /* ---------- Appointments ---------- */
    public function appointments(Request $r)
    {
        $q = Appointment::with('doctor.user', 'doctor.speciality', 'patient', 'transaction')->orderByDesc('date')->orderByDesc('time');
        if ($r->filled('status')) $q->where('status', $r->status);
        if ($r->filled('date')) $q->whereDate('date', $r->date);
        if ($r->filled('q')) $q->whereHas('patient', fn ($p) => $p->where('name', 'like', '%' . $r->q . '%'));
        return view('admin.appointments', ['appointments' => $q->paginate(15)->withQueryString(), 'counts' => DashboardStats::statusCounts(Appointment::get(['id', 'status']))]);
    }

    /** All payments on the platform: search reference / patient / doctor, status and date range. */
    public function transactions(Request $r)
    {
        $q = Transaction::with('patient', 'doctor.user', 'doctor.speciality', 'appointment')->latest('id');
        if ($r->filled('q')) $q->where(fn ($w) => $w->where('reference', 'like', '%' . $r->q . '%')
            ->orWhereHas('patient', fn ($p) => $p->where('name', 'like', '%' . $r->q . '%'))
            ->orWhereHas('doctor.user', fn ($u) => $u->where('name', 'like', '%' . $r->q . '%')));
        if ($r->filled('status')) $q->where('status', $r->status);
        if ($r->filled('from')) $q->whereDate('created_at', '>=', $r->from);
        if ($r->filled('to')) $q->whereDate('created_at', '<=', $r->to);

        $sum = fn ($s) => (int) Transaction::where('status', $s)->sum('amount');
        return view('admin.transactions', [
            'transactions' => $q->paginate(15)->withQueryString(),
            'totals' => ['paid' => $sum('paid'), 'pending' => $sum('pending'), 'refunded' => $sum('refunded')],
            'counts' => ['all' => Transaction::count()],
        ]);
    }

    public function updateAppointment(Request $r, Appointment $appointment)
    {
        $d = $r->validate(['status' => 'required|in:pending,confirmed,in_progress,completed,cancelled']);
        if ($error = $appointment->changeStatus($d['status'], 'admin')) {
            return back()->withErrors(['status' => $error]);
        }
        return back()->with('ok', 'Appointment marked ' . $d['status'] . '.');
    }

    /* ---------- Doctors ---------- */
    public function doctors()
    {
        return view('admin.doctors', ['doctors' => Doctor::with('user', 'speciality', 'city')->latest()->get()]);
    }

    public function createDoctor()
    {
        return view('admin.doctor-create', $this->formData(null));
    }

    public function editDoctor(Doctor $doctor)
    {
        return view('admin.doctor-create', $this->formData($doctor->load('user')));
    }

    /** Doctor profile page (admin view). */
    public function showDoctor(Doctor $doctor)
    {
        $doctor->load('user', 'speciality', 'city', 'hospital');
        return view('admin.doctor-show', [
            'doctor' => $doctor,
            'total' => $doctor->appointments()->count(),
            'completed' => $doctor->appointments()->where('status', 'completed')->count(),
            'patients' => $doctor->appointments()->distinct('patient_id')->count('patient_id'),
        ]);
    }

    /** Weekly availability overview of every doctor (search, sort, rows per page). */
    public function schedule(Request $r)
    {
        $q = Doctor::with('user', 'speciality');
        if ($r->filled('q')) {
            $s = '%' . $r->q . '%';
            $q->where(fn ($w) => $w
                ->whereHas('user', fn ($u) => $u->where('name', 'like', $s)->orWhere('phone', 'like', $s))
                ->orWhereHas('speciality', fn ($p) => $p->where('name', 'like', $s)));
        }
        if ($r->input('sort') === 'name') {
            $q->orderBy(User::select('name')->whereColumn('users.id', 'doctors.user_id'));
        } else {
            $q->latest('id');
        }
        $per = in_array((int) $r->per_page, [10, 25, 50]) ? (int) $r->per_page : 10;
        return view('admin.doctor-schedule', [
            'doctors' => $q->paginate($per)->withQueryString(),
            'total' => Doctor::count(), 'per' => $per,
        ]);
    }

    public function storeDoctor(Request $r)
    {
        $d = $r->validate($this->rules(null));
        $user = User::create(['name' => $d['name'], 'email' => $d['email'], 'password' => $d['password'],
            'phone' => $d['phone'] ?? null, 'role' => 'doctor', 'email_verified_at' => now()]);
        Doctor::create($this->payload($r, $d) + ['user_id' => $user->id, 'approved' => true]);
        return redirect()->route('admin.doctors')->with('ok', 'Doctor added and approved.');
    }

    public function updateDoctor(Request $r, Doctor $doctor)
    {
        $d = $r->validate($this->rules($doctor));
        $u = ['name' => $d['name'], 'email' => $d['email'], 'phone' => $d['phone'] ?? null];
        if (! empty($d['password'])) $u['password'] = $d['password'];
        $doctor->user->update($u);
        $doctor->update($this->payload($r, $d, $doctor->photo));
        return redirect()->route('admin.doctors')->with('ok', 'Doctor updated.');
    }

    public function toggleDoctor(Doctor $doctor)
    {
        if ($doctor->verification_status !== 'approved') {
            return back()->withErrors(['doctor' => 'Verify this doctor\'s licence and hospital first (Review button).']);
        }
        $doctor->update(['approved' => ! $doctor->approved]);
        return back()->with('ok', $doctor->approved ? 'Doctor approved.' : 'Doctor hidden from the website.');
    }

    public function destroyDoctor(Doctor $doctor)
    {
        $this->deletePhoto($doctor->photo);
        $doctor->user->delete(); // cascades to doctor + appointments
        return back()->with('ok', 'Doctor deleted.');
    }

    /* ---------- Doctor verification ---------- */
    public function verifications(Request $r)
    {
        $status = in_array($r->status, ['pending', 'rejected', 'approved']) ? $r->status : 'pending';
        $q = Doctor::with('user', 'speciality', 'city')->where('verification_status', $status);
        if ($status === 'approved') $q->whereNotNull('verified_at'); // only doctors that went through the sign-up check
        return view('admin.verifications', [
            'doctors' => $q->orderByDesc('submitted_at')->orderByDesc('id')->paginate(12)->withQueryString(),
            'status' => $status,
            'counts' => [
                'pending' => Doctor::where('verification_status', 'pending')->count(),
                'rejected' => Doctor::where('verification_status', 'rejected')->count(),
                'approved' => Doctor::where('verification_status', 'approved')->whereNotNull('verified_at')->count(),
            ],
        ]);
    }

    public function verifyDoctor(Doctor $doctor)
    {
        $doctor->load('user', 'speciality', 'city', 'verifier');
        $duplicate = $doctor->license_no
            ? Doctor::with('user')->where('license_no', $doctor->license_no)->where('id', '!=', $doctor->id)->first() : null;
        return view('admin.doctor-verify', ['doctor' => $doctor, 'duplicate' => $duplicate]);
    }

    public function approveDoctor(Request $r, Doctor $doctor)
    {
        $r->validate(['confirm' => 'accepted'], ['confirm.accepted' => 'Tick the box to confirm you checked the licence and hospital.']);
        if (! $doctor->user->hasVerifiedEmail()) {
            return back()->withErrors(['confirm' => 'This doctor has not verified the email address yet. Ask them to enter the OTP we emailed them first, then approve.']);
        }
        if (! $doctor->photo || ! $doctor->license_document || ! $doctor->id_document || ! $doctor->hospital_document) {
            return back()->withErrors(['confirm' => 'This doctor has not finished eKYC (profile photo and all three documents are required).']);
        }
        $doctor->update([
            'verification_status' => 'approved', 'approved' => true, 'rejection_reason' => null,
            'verified_at' => now(), 'verified_by' => $r->user()->id,
        ]);
        return redirect()->route('admin.doctors.verifications')->with('ok', $doctor->user->name . ' is verified and now live on the website.');
    }

    public function rejectDoctor(Request $r, Doctor $doctor)
    {
        $d = $r->validate(['rejection_reason' => 'required|string|min:5|max:500'], ['rejection_reason.required' => 'Write a reason so the doctor knows what to fix.']);
        $doctor->update([
            'verification_status' => 'rejected', 'approved' => false, 'rejection_reason' => $d['rejection_reason'],
            'verified_at' => null, 'verified_by' => null,
        ]);
        return redirect()->route('admin.doctors.verifications', ['status' => 'rejected'])->with('ok', 'Doctor rejected. They can fix the details and resubmit.');
    }

    /** Verification documents live on the private disk; only an admin can open them through this route. */
    public function document(Doctor $doctor, string $type)
    {
        abort_unless(isset(Doctor::DOCS[$type]), 404);
        $path = $doctor->{Doctor::DOCS[$type]};
        abort_unless($path && Storage::disk('local')->exists($path), 404, 'Document not found.');
        return Storage::disk('local')->response($path);
    }
}
