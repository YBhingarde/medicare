<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesDoctorProfile;
use App\Models\{Appointment, DoctorLeave, Review, Transaction};
use App\Support\{AppointmentNotifier, DashboardStats, MeetingLink};
use Carbon\Carbon;
use Illuminate\Http\Request;

class DoctorPanelController extends Controller
{
    use ManagesDoctorProfile;

    private function doctor(Request $r)
    {
        $doc = $r->user()->doctor;
        abort_unless($doc, 403, 'No doctor profile is linked to this account.');
        return $doc;
    }

    public function dashboard(Request $r)
    {
        $doc = $this->doctor($r);
        $appts = $doc->appointments()->with('patient', 'transaction')->orderBy('date')->orderBy('time')->get();
        $open = fn ($a) => $a->isOpen();
        $upcoming = $appts->filter(fn ($a) => $open($a) && ! $a->date->isPast())->values();
        $week = now()->subDays(7); $prev = now()->subDays(14);
        $cnt = fn ($from, $to = null) => $appts->filter(fn ($a) => $a->created_at >= $from && ($to === null || $a->created_at < $to))->count();

        return view('doctor.dashboard', [
            'doctor' => $doc, 'appts' => $appts,
            'cards' => [
                ['label' => 'Total Appointments', 'value' => $appts->count(), 'icon' => 'calendar2-check', 'color' => 'indigo', 'delta' => DashboardStats::pct($cnt($week), $cnt($prev, $week)), 'sub' => 'Change in last 7 days', 'url' => route('doctor.appointments')],
                ['label' => 'Today', 'value' => $appts->filter(fn ($a) => $a->date->isToday() && ! in_array($a->status, ['cancelled', 'expired']))->count(), 'icon' => 'calendar-day', 'color' => 'blue', 'sub' => 'Scheduled for today', 'url' => route('doctor.appointments', ['date' => now()->toDateString()])],
                ['label' => 'Pending Requests', 'value' => $appts->where('status', 'pending')->count(), 'icon' => 'hourglass-split', 'color' => 'amber', 'sub' => 'Waiting for your confirmation', 'url' => route('doctor.appointments', ['status' => 'pending'])],
                ['label' => 'Earnings', 'value' => (int) Transaction::where('doctor_id', $doc->id)->where('status', 'paid')->sum('amount'), 'icon' => 'currency-rupee', 'color' => 'green', 'prefix' => '₹', 'sub' => 'From completed visits', 'url' => route('doctor.transactions', ['status' => 'paid'])],
            ],
            'next' => $upcoming->first(), 'upcoming' => $upcoming->take(5),
            'chart' => DashboardStats::monthly($appts, now()->year), 'status' => DashboardStats::statusCounts($appts),
            'recent' => $appts->sortByDesc(fn ($a) => $a->date->format('Y-m-d') . $a->time)->take(6),
            'topPatients' => $appts->whereNotIn('status', ['cancelled', 'expired'])->groupBy('patient_id')->map(fn ($g) => ['patient' => $g->first()->patient, 'visits' => $g->count()])->sortByDesc('visits')->take(5),
            'totalPatients' => $appts->pluck('patient_id')->unique()->count(),
            'rating' => Review::summary($doc->id),
            'latestReviews' => $doc->reviews()->with('patient')->latest()->take(4)->get(),
        ]);
    }

    public function appointments(Request $r)
    {
        $doc = $this->doctor($r);
        $q = $doc->appointments()->with('patient')->orderByDesc('date')->orderByDesc('time');
        if ($r->filled('status')) $q->where('status', $r->status);
        if ($r->filled('date')) $q->whereDate('date', $r->date);
        if ($r->filled('q')) $q->whereHas('patient', fn ($p) => $p->where('name', 'like', '%' . $r->q . '%'));
        return view('doctor.appointments', ['appointments' => $q->paginate(12)->withQueryString()]);
    }

    /** Money side of the doctor's visits: search patient / reference, status and date range. */
    public function transactions(Request $r)
    {
        $doc = $this->doctor($r);
        $base = fn () => Transaction::where('doctor_id', $doc->id);
        $q = $base()->with('patient', 'appointment')->latest('id');
        if ($r->filled('q')) $q->where(fn ($w) => $w->where('reference', 'like', '%' . $r->q . '%')
            ->orWhereHas('patient', fn ($p) => $p->where('name', 'like', '%' . $r->q . '%')));
        if ($r->filled('status')) $q->where('status', $r->status);
        if ($r->filled('from')) $q->whereDate('created_at', '>=', $r->from);
        if ($r->filled('to')) $q->whereDate('created_at', '<=', $r->to);

        $sum = fn ($s) => (int) $base()->where('status', $s)->sum('amount');
        return view('doctor.transactions', [
            'transactions' => $q->paginate(12)->withQueryString(),
            'totals' => ['paid' => $sum('paid'), 'pending' => $sum('pending'), 'refunded' => $sum('refunded')],
            'counts' => ['all' => $base()->count()],
        ]);
    }

    public function patients(Request $r)
    {
        $doc = $this->doctor($r);
        $rows = $doc->appointments()->with('patient')->get()->groupBy('patient_id')->map(fn ($g) => [
            'patient' => $g->first()->patient, 'visits' => $g->where('status', '!=', 'cancelled')->count(),
            'last' => $g->sortByDesc('date')->first()->date, 'completed' => $g->where('status', 'completed')->count(),
        ]);
        if ($r->filled('q')) $rows = $rows->filter(fn ($x) => str_contains(mb_strtolower($x['patient']->name), mb_strtolower($r->q)));
        return view('doctor.patients', ['rows' => $rows->sortByDesc('last')->values()]);
    }

    public function update(Request $r, Appointment $appointment)
    {
        abort_unless($appointment->doctor_id === $this->doctor($r)->id, 403);
        $d = $r->validate([
            'status' => 'required|in:pending,confirmed,in_progress,completed,cancelled',
            'prescription' => 'nullable|string|max:2000',
            'meeting_link' => 'nullable|url|max:255',
            'generate_meet' => 'nullable|boolean',
        ]);

        // status changes follow the rules in Appointment::TRANSITIONS (and "completed" only from the appointment date)
        if ($error = $appointment->changeStatus($d['status'], 'doctor')) {
            return back()->withErrors(['status' => $error]);
        }

        $extra = [];
        // quick-action buttons send only the status; the prescription is saved only when it was sent
        if ($r->has('prescription') && in_array($appointment->status, ['confirmed', 'in_progress', 'completed'])) $extra['prescription'] = $d['prescription'];
        // online visit: the doctor adds / changes the video-call link
        $linkChanged = false;
        if ($r->has('meeting_link') && $appointment->mode === 'Online' && $appointment->isOpen()) {
            // "Create Google Meet link" button: a real Meet room is made by Google, nothing to paste
            if ($r->boolean('generate_meet')) {
                try {
                    $meet = MeetingLink::google($appointment);
                } catch (\Throwable $e) {
                    report($e);
                    $meet = null;
                }
                if (! $meet) return back()->withErrors(['meeting_link' => 'Google Meet link could not be created. Check the GOOGLE_* keys in .env, or paste a link manually.']);
                $d['meeting_link'] = $meet;
            }
            // empty box = keep the current (auto-made) link, or make one if there is none yet
            $link = $d['meeting_link'] ?: $appointment->meeting_link ?: MeetingLink::generate($appointment);
            $linkChanged = $link !== $appointment->meeting_link;
            $extra['meeting_link'] = $link;
        }
        if ($extra) $appointment->update($extra);
        if ($linkChanged && in_array($appointment->status, ['confirmed', 'in_progress'])) AppointmentNotifier::send($appointment, 'link', 'doctor');

        return back()->with('ok', 'Appointment updated.');
    }

    /* ---------- Leave days ---------- */
    public function leaves(Request $r)
    {
        $doc = $this->doctor($r);
        return view('doctor.leaves', [
            'doctor' => $doc,
            'leaves' => $doc->leaves()->whereDate('date', '>=', now()->toDateString())->orderBy('date')->get(),
            'past' => $doc->leaves()->whereDate('date', '<', now()->toDateString())->orderByDesc('date')->take(5)->get(),
        ]);
    }

    public function storeLeave(Request $r)
    {
        $doc = $this->doctor($r);
        $d = $r->validate([
            'from' => 'required|date|after_or_equal:today',
            'to' => 'nullable|date|after_or_equal:from',
            'reason' => 'nullable|string|max:150',
            'cancel_existing' => 'nullable|boolean',
        ]);
        $from = Carbon::parse($d['from'])->startOfDay();
        $to = Carbon::parse($d['to'] ?? $d['from'])->startOfDay();
        if ($from->diffInDays($to) > 60) return back()->withInput()->withErrors(['to' => 'A leave can be at most 60 days long. Add it in parts.']);

        $dates = [];
        for ($day = $from->copy(); $day->lte($to); $day->addDay()) $dates[] = $day->toDateString();

        // patients who already booked on these days
        $booked = $doc->appointments()->with('patient')->whereIn('status', ['pending', 'confirmed'])->whereIn('date', $dates)->get();
        if ($booked->isNotEmpty() && ! $r->boolean('cancel_existing')) {
            return back()->withInput()->withErrors(['from' => $booked->count() . ' open appointment(s) already exist on these days. Tick "Cancel those appointments and notify the patients" to continue.']);
        }

        foreach ($dates as $date) {
            DoctorLeave::firstOrCreate(['doctor_id' => $doc->id, 'date' => $date], ['reason' => $d['reason'] ?? null]);
        }
        foreach ($booked as $a) $a->changeStatus('cancelled', 'doctor'); // sends the cancellation e-mails

        $msg = count($dates) . ' leave day(s) saved. Patients can no longer book these days.';
        if ($booked->isNotEmpty()) $msg .= ' ' . $booked->count() . ' appointment(s) cancelled and the patients were e-mailed.';
        return back()->with('ok', $msg);
    }

    public function destroyLeave(Request $r, DoctorLeave $leave)
    {
        abort_unless($leave->doctor_id === $this->doctor($r)->id, 403);
        $leave->delete();
        return back()->with('ok', 'Leave removed. Bookings are open again for that day.');
    }

    /** A doctor edits only their own profile, photo and weekly availability. */
    public function editProfile(Request $r)
    {
        return view('admin.doctor-create', $this->formData($this->doctor($r)->load('user'), 'doctor'));
    }

    public function updateProfile(Request $r)
    {
        $doctor = $this->doctor($r);
        $d = $r->validate($this->rules($doctor));
        $u = ['name' => $d['name'], 'email' => $d['email'], 'phone' => $d['phone'] ?? null];
        if (! empty($d['password'])) $u['password'] = $d['password'];
        $doctor->user->update($u);
        $doctor->update($this->payload($r, $d, $doctor->photo));
        return redirect()->route('doctor.dashboard')->with('ok', 'Your profile has been updated.');
    }
}