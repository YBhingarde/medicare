<?php
namespace App\Http\Controllers;

use App\Models\{City, Doctor, Hospital, Speciality, User};
use App\Support\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Storage};
use Illuminate\Validation\Rule;

/** Public doctor sign-up + the "verification status" page a doctor sees until an admin approves them. */
class DoctorRegistrationController extends Controller
{
    public function create()
    {
        return view('auth.register-doctor', [
            'specialities' => Speciality::orderBy('name')->get(),
            'cities' => City::orderBy('name')->get(),
            'hospitals' => Hospital::orderBy('name')->pluck('name'),
        ]);
    }

    public function store(Request $r)
    {
        $this->normalize($r);
        $d = $r->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users,email',
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'password' => 'required|string|min:8|confirmed',
            'speciality_id' => 'required|exists:specialities,id',
            'city_id' => 'required|exists:cities,id',
            'qualification' => 'required|string|max:150',
            'experience_years' => 'required|integer|min:0|max:70',
            'fees' => 'required|integer|min:0|max:100000',
            'license_no' => ['required', 'string', 'max:60', Rule::unique('doctors', 'license_no')],
            'license_council' => 'required|string|max:120',
            'license_year' => 'required|integer|min:1950|max:' . now()->year,
            'hospital_name' => 'required|string|max:150',
            'hospital_address' => 'required|string|max:255',
            'hospital_phone' => 'nullable|string|max:20',
        ], [
            'license_no.unique' => 'This licence number is already registered. If it is yours, contact support.',
        ]);

        $user = DB::transaction(function () use ($r, $d) {
            $user = User::create(['name' => $d['name'], 'email' => $d['email'], 'phone' => $d['phone'],
                'password' => $d['password'], 'role' => 'doctor']);
            // 'incomplete' = account created, eKYC (documents + photo) not submitted yet
            Doctor::create($this->details($d) + [
                'user_id' => $user->id, 'speciality_id' => $d['speciality_id'], 'city_id' => $d['city_id'],
                'qualification' => $d['qualification'], 'experience_years' => $d['experience_years'], 'fees' => $d['fees'],
                'approved' => false, 'verification_status' => 'incomplete',
            ]);
            return $user;
        });

        Auth::login($user);
        $r->session()->regenerate();
        $sent = Otp::send($user->email, Otp::VERIFY, $user->name) === 'sent';
        return redirect()->route('doctor.verification')->with('ok', $sent
            ? 'Account created. We emailed a 6-digit code to ' . $user->email . ' - enter it below to open your dashboard.'
            : 'Account created. We could not send the verification code just now - please press "Resend code" below.');
    }

    /** Email OTP page. Once the email is verified the doctor goes straight to the dashboard. */
    public function status(Request $r)
    {
        abort_unless($r->user()->doctor, 403, 'No doctor profile is linked to this account.');
        if ($r->user()->hasVerifiedEmail()) return redirect()->route('doctor.dashboard');
        return view('doctor.verification');
    }

    /** eKYC page (inside the dashboard): upload documents + photo, see status, fix a rejection. */
    public function kyc(Request $r)
    {
        $doctor = $r->user()->doctor;
        abort_unless($doctor, 403, 'No doctor profile is linked to this account.');
        return view('doctor.kyc', ['doctor' => $doctor->load('user', 'speciality', 'city'), 'hospitals' => Hospital::orderBy('name')->pluck('name')]);
    }

    /** First submission (status incomplete) or resubmission after a rejection: goes into the admin queue as 'pending'. */
    public function submitKyc(Request $r)
    {
        $doctor = $r->user()->doctor;
        abort_unless($doctor && in_array($doctor->verification_status, ['incomplete', 'rejected']), 403);
        $this->normalize($r);

        $file = fn (string $col) => ($doctor->$col ? 'nullable' : 'required') . '|file|mimes:pdf,jpg,jpeg,png|max:4096';
        $rules = [
            'photo' => ($doctor->photo ? 'nullable' : 'required') . '|image|mimes:jpg,jpeg,png,webp|max:2048',
            'license_document' => $file('license_document'),
            'id_document' => $file('id_document'),
            'hospital_document' => $file('hospital_document'),
            'declaration' => 'accepted',
        ];
        if ($doctor->verification_status === 'rejected') { // details can be corrected after a rejection
            $rules += [
                'license_no' => ['required', 'string', 'max:60', Rule::unique('doctors', 'license_no')->ignore($doctor->id)],
                'license_council' => 'required|string|max:120',
                'license_year' => 'required|integer|min:1950|max:' . now()->year,
                'hospital_name' => 'required|string|max:150',
                'hospital_address' => 'required|string|max:255',
                'hospital_phone' => 'nullable|string|max:20',
            ];
        }
        $d = $r->validate($rules, [
            'photo.required' => 'Please upload your profile photo. Patients need to see who they are booking.',
            'license_document.required' => 'Please upload your medical licence certificate.',
            'id_document.required' => 'Please upload a government photo ID.',
            'hospital_document.required' => 'Please upload your hospital proof.',
            'declaration.accepted' => 'Please confirm that the details and documents are genuine.',
        ]);

        $update = $this->storeFiles($r, $r->user()->id, $doctor) + [
            'verification_status' => 'pending', 'rejection_reason' => null, 'submitted_at' => now(),
        ];
        if ($doctor->verification_status === 'rejected') $update += $this->details($d);
        if ($r->hasFile('photo')) {
            $f = $r->file('photo');
            if ($doctor->photo && str_starts_with($doctor->photo, 'uploads/doctors/') && is_file(public_path($doctor->photo))) @unlink(public_path($doctor->photo));
            $name = uniqid('doc_') . '.' . $f->extension();
            $f->move(public_path('uploads/doctors'), $name);
            $update['photo'] = 'uploads/doctors/' . $name;
        }
        $doctor->update($update);
        return redirect()->route('doctor.kyc')->with('ok', 'eKYC submitted. Our team will review it and your profile goes live once approved.');
    }

    private function normalize(Request $r): void
    {
        if ($r->has('license_no')) $r->merge(['license_no' => strtoupper(trim((string) $r->input('license_no')))]);
    }

    private function details(array $d): array
    {
        return collect($d)->only(['license_no', 'license_council', 'license_year', 'hospital_name', 'hospital_address', 'hospital_phone'])->all();
    }

    /** Saves uploaded documents on the private disk and returns [column => path]; replaces old files when a new one is sent. */
    private function storeFiles(Request $r, int $userId, ?Doctor $old = null): array
    {
        $out = [];
        foreach (Doctor::DOCS as $field => $col) {
            if (! $r->hasFile($col)) continue;
            if ($old?->$col) Storage::disk('local')->delete($old->$col);
            $out[$col] = $r->file($col)->store("doctor-verification/$userId", 'local');
        }
        return $out;
    }
}
