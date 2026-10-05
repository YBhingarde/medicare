<?php
namespace App\Http\Controllers;

use App\Models\{City, Doctor, Speciality, Appointment, Review, Transaction};
use App\Support\{AppointmentNotifier, DashboardStats, Razorpay};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicController extends Controller
{
    public function home()
    {
        $featured = Doctor::with(['user', 'speciality', 'city'])->withAvg('reviews', 'rating')->withCount('reviews')->where('approved', true)->latest()->take(8)->get();
        Doctor::preloadSlots($featured); // 2 queries for all 8 doctors instead of 2 per doctor per day
        $featured->each(fn ($d) => $d->next_slot = $d->nextSlot());

        return view('home', [
            'specialities' => Speciality::orderBy('name')->get(),
            'cities' => City::orderBy('name')->get(),
            'doctorCount' => Doctor::where('approved', true)->count(),
            'featured' => $featured,
            'bookedCount' => Appointment::count(),
            'ratingSummary' => Review::summary(),
            'reviews' => Review::with('doctor.user', 'doctor.speciality', 'patient.patientProfile')->where('is_visible', true)->where('rating', '>=', 4)->whereNotNull('comment')->where('comment', '!=', '')->latest()->take(10)->get(),
        ]);
    }

    public function doctors(Request $r)
    {
        $q = Doctor::where('approved', true);
        if ($r->filled('q')) $q->whereHas('user', fn ($u) => $u->where('name', 'like', '%' . $r->q . '%'));
        if ($r->filled('speciality')) $q->where('speciality_id', $r->speciality);
        if ($r->filled('city')) $q->where('city_id', $r->city);
        // slider max = 5000 means "no fee limit"
        $maxFees = (int) $r->input('max_fees', 0);
        if ($maxFees > 0 && $maxFees < 5000) $q->where('fees', '<=', $maxFees);
        if ($r->boolean('online')) $q->whereIn('appointment_type', ['Online', 'Both']);
        if ($r->boolean('today')) {
            $today = now()->toDateString();
            // 1) the database drops doctors on leave today, 2) leaves + bookings of the rest are loaded in bulk (2 queries),
            // 3) only the light slot calculation runs in PHP - no query per doctor and no relations are loaded here
            $candidates = (clone $q)->whereDoesntHave('leaves', fn ($l) => $l->whereDate('date', $today))->get();
            Doctor::preloadSlots($candidates, 0);
            $q->whereIn('id', $candidates->filter(fn ($d) => count($d->slotsFor($today)) > 0)->modelKeys());
        }
        $q->with(['user', 'speciality', 'city', 'hospital'])->withAvg('reviews', 'rating')->withCount('reviews');
        match ($r->input('sort', 'fees_asc')) {
            'fees_desc' => $q->orderByDesc('fees'),
            'exp' => $q->orderByDesc('experience_years'),
            'rating' => $q->orderByDesc('reviews_avg_rating')->orderByDesc('reviews_count'),
            default => $q->orderBy('fees'),
        };
        $doctors = $q->paginate(9)->withQueryString();
        Doctor::preloadSlots($doctors->getCollection()); // next-free-slot of the 9 doctors on this page in 2 queries
        $doctors->getCollection()->each(fn ($d) => $d->next_slot = $d->nextSlot());
        return view('doctors', [
            'doctors' => $doctors,
            'specialities' => Speciality::orderBy('name')->get(),
            'cities' => City::orderBy('name')->get(),
        ]);
    }

    public function show(Request $r, Doctor $doctor)
    {
        abort_unless($doctor->approved, 404);
        $date = $r->input('date', now()->toDateString());
        if ($date < now()->toDateString()) $date = now()->toDateString();
        $maxDate = now()->addDays($doctor->advance_days ?: 30)->toDateString();
        if ($date > $maxDate) $date = $maxDate;
        $board = $doctor->slotBoard($date);
        return view('doctor-show', [
            'doctor' => $doctor->load(['user', 'speciality', 'city', 'hospital']),
            'date' => $date, 'maxDate' => $maxDate,
            'board' => $board,
            'slots' => array_values(array_map(fn ($s) => $s['time'], array_filter($board, fn ($s) => ! $s['full']))),
            'rebook' => $r->boolean('rebook'),
            'onLeave' => $doctor->isOnLeave($date),
            'summary' => Review::summary($doctor->id),
            'reviews' => $doctor->reviews()->with('patient')->latest()->take(8)->get(),
        ]);
    }

    public function book(Request $r, Doctor $doctor)
    {
        abort_unless($r->user()->role === 'patient', 403, 'Only patients can book appointments.');
        $data = $r->validate([
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required|date_format:H:i',
            'reason' => 'nullable|string|max:255',
            'mode' => 'nullable|in:In-person,Online',
            'payment' => 'nullable|in:online,clinic',
        ]);
        // visit mode follows the doctor's appointment type; only "Both" lets the patient choose
        $data['mode'] = match ($doctor->appointment_type) {
            'Online' => 'Online', 'Both' => $data['mode'] ?? 'In-person', default => 'In-person',
        };
        // All checks + the insert run inside one transaction while this doctor's row is locked, so two patients
        // can never take the last seat of a slot together (the second one waits, then sees the slot as full).
        $result = DB::transaction(function () use ($r, $doctor, $data) {
            $doc = Doctor::whereKey($doctor->id)->lockForUpdate()->firstOrFail(); // fresh copy: leaves + bookings are read after the lock
            if ($data['date'] > now()->addDays($doc->advance_days ?: 30)->toDateString()) {
                return 'This doctor accepts bookings only ' . ($doc->advance_days ?: 30) . ' days in advance.';
            }
            if ($doc->isOnLeave($data['date'])) {
                return 'The doctor is on leave on this date. Please pick another day.';
            }
            if (! in_array($data['time'], $doc->slotsFor($data['date']))) {
                return 'That slot is no longer available. Pick another time.';
            }
            // the same patient cannot hold two open bookings with the same doctor at the same time
            if ($this->hasDuplicate($r->user()->id, $doc->id, $data['date'], $data['time'])) {
                return 'You already have an appointment with this doctor at that time.';
            }
            // Every booking is a brand-new row. Cancelled / expired rows stay as history and are never re-used.
            return Appointment::create([
                'doctor_id' => $doc->id, 'patient_id' => $r->user()->id,
                'date' => $data['date'], 'time' => $data['time'],
                'reason' => $data['reason'] ?? null, 'status' => 'pending', 'mode' => $data['mode'],
            ]);
        });

        if (is_string($result)) return back()->withErrors(['time' => $result]);

        // Online payment: when Razorpay is set up, video visits are always prepaid and clinic visits can be prepaid by choice.
        // The slot is held while the patient pays; nobody is e-mailed (and the doctor cannot confirm) until the money is in.
        $fee = (int) $doctor->fees;
        $payOnline = Razorpay::isReady() && $fee > 0 && ($data['mode'] === 'Online' || ($data['payment'] ?? 'clinic') === 'online');
        if ($payOnline) {
            Transaction::where('appointment_id', $result->id)->update(['pay_online' => true, 'method' => 'Online (Razorpay)']);
            return redirect()->route('patient.pay', $result);
        }

        AppointmentNotifier::send($result, 'booked', 'patient'); // after the commit
        return redirect()->route('patient.appointments')->with('ok', 'Appointment requested. The doctor will confirm it soon.');
    }

    public function patientDashboard(Request $r)
    {
        $appts = $r->user()->appointments()->with('doctor.user', 'doctor.speciality', 'review', 'transaction')->orderByDesc('date')->orderByDesc('time')->get();
        $open = fn ($a) => $a->isOpen();
        $done = $appts->where('status', 'completed');
        return view('patient.dashboard', [
            'cards' => [
                ['label' => 'Total Appointments', 'value' => $appts->count(), 'icon' => 'calendar2-check', 'color' => 'indigo', 'sub' => 'All bookings', 'url' => route('patient.appointments')],
                ['label' => 'Upcoming', 'value' => $appts->filter(fn ($a) => $open($a) && ! $a->date->isPast())->count(), 'icon' => 'clock-history', 'color' => 'blue', 'sub' => 'Pending or confirmed', 'url' => route('patient.appointments', ['filter' => 'upcoming'])],
                ['label' => 'Completed', 'value' => $done->count(), 'icon' => 'check2-circle', 'color' => 'green', 'sub' => 'Consultations done', 'url' => route('patient.appointments', ['status' => 'completed'])],
                ['label' => 'Fees Paid', 'value' => $done->sum(fn ($a) => $a->fee), 'icon' => 'currency-rupee', 'color' => 'amber', 'prefix' => '₹', 'sub' => 'On completed visits', 'url' => route('patient.transactions', ['status' => 'paid'])],
            ],
            'myDoctors' => $appts->whereNotIn('status', ['cancelled', 'expired'])->groupBy('doctor_id')->map(fn ($g) => ['doctor' => $g->first()->doctor, 'visits' => $g->count()])->sortByDesc('visits')->take(5),
            'prescriptions' => $appts->filter(fn ($a) => $a->hasPrescription())->take(5),
            'activity' => $appts->sortByDesc('updated_at')->take(5),
            'departments' => $appts->whereNotIn('status', ['cancelled', 'expired'])->groupBy(fn ($a) => $a->doctor->speciality->name)->map->count()->sortDesc(),
            'status' => DashboardStats::statusCounts($appts),
            'recent' => $appts->take(6),
            'next' => $appts->filter(fn ($a) => $open($a) && ! $a->date->isPast())->sortBy(fn ($a) => $a->date->format('Y-m-d') . $a->time)->first(),
        ]);
    }

    public function myAppointments(Request $r)
    {
        $q = $r->user()->appointments()->with('doctor.user', 'doctor.speciality', 'review', 'transaction')->orderByDesc('date')->orderByDesc('time');
        $filter = $r->input('filter') === 'upcoming' ? 'upcoming' : null;
        $status = in_array($r->input('status'), ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'expired'], true) ? $r->input('status') : null;
        if ($filter) $q->whereIn('status', Appointment::OPEN)->whereDate('date', '>=', now()->toDateString());
        elseif ($status) $q->where('status', $status);

        return view('patient.appointments', ['appointments' => $q->get(), 'filter' => $filter, 'status' => $status]);
    }

    private function hasDuplicate(int $patientId, int $doctorId, string $date, string $time, ?int $ignoreId = null): bool
    {
        return Appointment::where('patient_id', $patientId)->where('doctor_id', $doctorId)
            ->whereDate('date', $date)->where('time', $time)
            ->whereIn('status', Appointment::OPEN)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists();
    }

    private function cutoffMessage(string $verb): string
    {
        return 'You can ' . $verb . ' an appointment only up to ' . Appointment::CANCEL_CUTOFF_HOURS . ' hours before the visit time.';
    }

    public function cancel(Request $r, Appointment $appointment)
    {
        abort_unless($appointment->patient_id === $r->user()->id, 403);
        if (! $appointment->isOpen()) return back()->withErrors(['cancel' => 'This appointment is already ' . $appointment->status . '.']);
        if (! $appointment->patientCanChange()) return back()->withErrors(['cancel' => $this->cutoffMessage('cancel')]);
        $appointment->changeStatus('cancelled', 'patient');
        return back()->with('ok', 'Appointment cancelled.');
    }

    /** Reschedule page: pick a new date and time with the same doctor. */
    public function rescheduleForm(Request $r, Appointment $appointment)
    {
        abort_unless($appointment->patient_id === $r->user()->id, 403);
        if (! $appointment->patientCanChange()) {
            return redirect()->route('patient.appointments')->withErrors(['cancel' => $this->cutoffMessage('reschedule')]);
        }
        $doctor = $appointment->doctor->load('user', 'speciality');
        $date = $r->input('date', $appointment->date->toDateString());
        if ($date < now()->toDateString()) $date = now()->toDateString();
        $maxDate = now()->addDays($doctor->advance_days ?: 30)->toDateString();
        if ($date > $maxDate) $date = $maxDate;
        return view('patient.reschedule', [
            'a' => $appointment, 'doctor' => $doctor, 'date' => $date, 'maxDate' => $maxDate,
            'board' => $doctor->slotBoard($date), 'onLeave' => $doctor->isOnLeave($date),
        ]);
    }

    public function reschedule(Request $r, Appointment $appointment)
    {
        abort_unless($appointment->patient_id === $r->user()->id, 403);
        if (! $appointment->patientCanChange()) return back()->withErrors(['time' => $this->cutoffMessage('reschedule')]);
        $data = $r->validate(['date' => 'required|date|after_or_equal:today', 'time' => 'required|date_format:H:i']);
        $doctor = $appointment->doctor;

        // same lock as booking: the checks and the update happen while the doctor's row is locked
        $error = DB::transaction(function () use ($appointment, $doctor, $data, $r) {
            $doc = Doctor::whereKey($doctor->id)->lockForUpdate()->firstOrFail();
            $appointment->refresh();
            if (! $appointment->patientCanChange()) return $this->cutoffMessage('reschedule');
            if ($data['date'] > now()->addDays($doc->advance_days ?: 30)->toDateString()) {
                return 'This doctor accepts bookings only ' . ($doc->advance_days ?: 30) . ' days in advance.';
            }
            if ($data['date'] === $appointment->date->toDateString() && $data['time'] === substr((string) $appointment->time, 0, 5)) {
                return 'That is your current time. Pick a different slot.';
            }
            if ($doc->isOnLeave($data['date'])) {
                return 'The doctor is on leave on this date. Please pick another day.';
            }
            if (! in_array($data['time'], $doc->slotsFor($data['date']))) {
                return 'That slot is no longer available. Pick another time.';
            }
            if ($this->hasDuplicate($r->user()->id, $doc->id, $data['date'], $data['time'], $appointment->id)) {
                return 'You already have an appointment with this doctor at that time.';
            }
            // the doctor has to confirm the new time again
            $appointment->update(['date' => $data['date'], 'time' => $data['time'], 'status' => 'pending']);
            return null;
        });

        if ($error) return back()->withErrors(['time' => $error]);

        AppointmentNotifier::send($appointment, 'rescheduled', 'patient');
        return redirect()->route('patient.appointments')->with('ok', 'Appointment rescheduled. The doctor will confirm the new time.');
    }
}
