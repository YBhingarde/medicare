<?php
namespace App\Http\Controllers;

use App\Models\{Doctor, PatientProfile, Transaction};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Patient's own profile page: about, vitals, appointments and transactions (with receipts). */
class PatientProfileController extends Controller
{
    private function profile(Request $r): PatientProfile
    {
        return $r->user()->patientProfile ?: PatientProfile::create(['user_id' => $r->user()->id]);
    }

    public function show(Request $r)
    {
        $user = $r->user();
        $profile = $this->profile($r)->load('preferredDoctor.user', 'preferredDoctor.speciality');
        $tab = $r->input('tab') === 'transactions' ? 'transactions' : 'appointments';

        $all = $user->appointments()->with('doctor.user', 'doctor.speciality', 'transaction')->get();
        $last = $all->where('status', 'completed')->sortByDesc('date')->first();

        // Appointments tab: search doctor, status, date range
        $apptQ = $user->appointments()->with('doctor.user', 'doctor.speciality')->orderByDesc('date')->orderByDesc('time');
        if ($tab === 'appointments') {
            if ($r->filled('q')) $apptQ->whereHas('doctor.user', fn ($u) => $u->where('name', 'like', '%' . $r->q . '%'));
            if ($r->filled('status')) $apptQ->where('status', $r->status);
            if ($r->filled('from')) $apptQ->whereDate('date', '>=', $r->from);
            if ($r->filled('to')) $apptQ->whereDate('date', '<=', $r->to);
        }

        // Transactions tab: search doctor / reference, status, date range
        $txQ = $user->transactions()->with('doctor.user', 'doctor.speciality', 'appointment')->latest('id');
        if ($tab === 'transactions') {
            if ($r->filled('q')) $txQ->where(fn ($w) => $w->where('reference', 'like', '%' . $r->q . '%')
                ->orWhereHas('doctor.user', fn ($u) => $u->where('name', 'like', '%' . $r->q . '%')));
            if ($r->filled('status')) $txQ->where('status', $r->status);
            if ($r->filled('from')) $txQ->whereDate('created_at', '>=', $r->from);
            if ($r->filled('to')) $txQ->whereDate('created_at', '<=', $r->to);
        }

        $sum = fn ($s) => (int) $user->transactions()->where('status', $s)->sum('amount');
        return view('patient.profile', [
            'user' => $user, 'profile' => $profile, 'tab' => $tab, 'lastVisit' => $last?->date,
            'appointments' => $tab === 'appointments' ? $apptQ->paginate(8)->withQueryString() : collect(),
            'transactions' => $tab === 'transactions' ? $txQ->paginate(8)->withQueryString() : collect(),
            'counts' => ['appointments' => $all->count(), 'transactions' => $user->transactions()->count()],
            'totals' => ['paid' => $sum('paid'), 'pending' => $sum('pending'), 'refunded' => $sum('refunded')],
            'myDoctors' => $all->where('status', '!=', 'cancelled')->groupBy('doctor_id')
                ->map(fn ($g) => ['doctor' => $g->first()->doctor, 'visits' => $g->count()])->sortByDesc('visits')->take(4),
        ]);
    }

    /** Dedicated Transactions page (sidebar): totals, filters, receipts and "Pay now". */
    public function transactions(Request $r)
    {
        $user = $r->user();
        $q = $user->transactions()->with('doctor.user', 'doctor.speciality', 'appointment')->latest('id');
        if ($r->filled('q')) $q->where(fn ($w) => $w->where('reference', 'like', '%' . $r->q . '%')
            ->orWhereHas('doctor.user', fn ($u) => $u->where('name', 'like', '%' . $r->q . '%')));
        if ($r->filled('status')) $q->where('status', $r->status);
        if ($r->filled('from')) $q->whereDate('created_at', '>=', $r->from);
        if ($r->filled('to')) $q->whereDate('created_at', '<=', $r->to);

        $sum = fn ($s) => (int) $user->transactions()->where('status', $s)->sum('amount');
        return view('patient.transactions', [
            'transactions' => $q->paginate(10)->withQueryString(),
            'totals' => ['paid' => $sum('paid'), 'pending' => $sum('pending'), 'refunded' => $sum('refunded')],
            'counts' => ['all' => $user->transactions()->count()],
        ]);
    }

    public function edit(Request $r)
    {
        return view('patient.profile-edit', [
            'user' => $r->user(), 'profile' => $this->profile($r),
            'doctors' => Doctor::with('user', 'speciality')->where('approved', true)->get()->sortBy(fn ($d) => $d->user->name),
        ]);
    }

    public function update(Request $r)
    {
        $user = $r->user();
        $profile = $this->profile($r);
        $d = $r->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'dob' => 'nullable|date|before:today', 'gender' => 'nullable|in:Male,Female,Other',
            'blood_group' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'address' => 'nullable|string|max:255', 'city' => 'nullable|string|max:80', 'pincode' => 'nullable|string|max:10',
            'emergency_name' => 'nullable|string|max:100', 'emergency_phone' => 'nullable|string|max:20',
            'allergies' => 'nullable|string|max:500', 'medical_notes' => 'nullable|string|max:1000',
            'preferred_doctor_id' => ['nullable', Rule::exists('doctors', 'id')->where('approved', true)],
            'bp' => ['nullable', 'regex:/^\d{2,3}\/\d{2,3}$/'], 'heart_rate' => 'nullable|integer|min:20|max:250',
            'spo2' => 'nullable|integer|min:50|max:100', 'temperature' => 'nullable|numeric|min:30|max:45',
            'respiratory_rate' => 'nullable|integer|min:5|max:70', 'weight' => 'nullable|numeric|min:1|max:400',
            'photo' => 'nullable|image|max:2048',
        ], ['bp.regex' => 'Blood pressure should look like 120/80.']);

        $user->update(['name' => $d['name'], 'email' => $d['email'], 'phone' => $d['phone'] ?? null]);

        $data = collect($d)->except(['name', 'email', 'phone', 'photo'])->map(fn ($v) => $v === '' ? null : $v)->all();
        $vitals = ['bp', 'heart_rate', 'spo2', 'temperature', 'respiratory_rate', 'weight'];
        $before = collect($vitals)->map(fn ($k) => (string) $profile->$k)->all();
        $after = collect($vitals)->map(fn ($k) => (string) ($data[$k] ?? ''))->all();
        if ($before !== $after) $data['vitals_updated_at'] = now();

        if ($r->hasFile('photo')) {
            $this->deletePhoto($profile->photo);
            $name = uniqid('pt_') . '.' . $r->file('photo')->extension();
            $r->file('photo')->move(public_path('uploads/patients'), $name);
            $data['photo'] = 'uploads/patients/' . $name;
        }
        $profile->update($data);
        return redirect()->route('patient.profile')->with('ok', 'Profile updated.');
    }

    /** Printable receipt for one transaction. */
    public function receipt(Request $r, Transaction $transaction)
    {
        abort_unless($transaction->patient_id === $r->user()->id, 403);
        return view('patient.receipt', ['tx' => $transaction->load('doctor.user', 'doctor.speciality', 'doctor.hospital', 'appointment', 'patient')]);
    }

    private function deletePhoto(?string $path): void
    {
        if ($path && str_starts_with($path, 'uploads/patients/') && is_file(public_path($path))) @unlink(public_path($path));
    }
}
