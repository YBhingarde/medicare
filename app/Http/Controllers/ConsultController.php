<?php
namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;

/**
 * The video room of an Online appointment, inside MediCare (Jitsi in an iframe).
 * Only the patient and the doctor of THAT appointment may open it; everybody else gets 403.
 */
class ConsultController extends Controller
{
    public function show(Request $r, Appointment $appointment)
    {
        $role = $this->role($r, $appointment);
        $a = $appointment;

        // the doctor pasted a Google Meet / Zoom link of their own: that visit is not held in this room
        if (! $a->usesAppRoom()) return redirect()->away($a->meeting_link);

        $phase = $a->consultPhase();
        // the patient walks in after the doctor started: the visit is now "In progress"
        if ($phase === 'live' && $role === 'patient') {
            $a->markJoined();
            $phase = $a->consultPhase();
        }

        $me = $role === 'doctor' ? $a->doctor->user : $a->patient;
        return view('consult.room', [
            'a' => $a, 'role' => $role, 'phase' => $phase,
            'embed' => $phase === 'live' ? $a->jitsiEmbedUrl($me->name) : null,
        ]);
    }

    /** Polled by the room page every few seconds: when the phase changes the page reloads itself. */
    public function state(Request $r, Appointment $appointment)
    {
        $this->role($r, $appointment);
        return response()->json(['phase' => $appointment->consultPhase(), 'status' => $appointment->status])
            ->header('Cache-Control', 'no-store');
    }

    public function start(Request $r, Appointment $appointment)
    {
        abort_unless($this->role($r, $appointment) === 'doctor', 403);
        if ($appointment->consultPhase() !== 'waiting') {
            return back()->withErrors(['consult' => 'The consultation can be started only from ' . Appointment::CONSULT_EARLY_MINUTES . ' minutes before the visit until it ends.']);
        }
        $appointment->update(['consult_started_at' => $appointment->consult_started_at ?? now()]);
        return redirect()->route('consult.show', $appointment);
    }

    public function complete(Request $r, Appointment $appointment)
    {
        abort_unless($this->role($r, $appointment) === 'doctor', 403);
        if ($error = $appointment->changeStatus('completed', 'doctor')) {
            return back()->withErrors(['consult' => $error]);
        }
        return redirect()->route('doctor.appointments')->with('ok', 'Visit marked completed.');
    }

    /** 'patient' | 'doctor' for the two people of this visit, 403 for anybody else (also for admins). */
    private function role(Request $r, Appointment $a): string
    {
        $a->loadMissing('doctor.user', 'patient');
        $u = $r->user();
        $role = match (true) {
            $u->id === $a->patient_id => 'patient',
            $a->doctor && $u->id === $a->doctor->user_id => 'doctor',
            default => abort(403, 'This consultation room is only for the patient and the doctor of this appointment.'),
        };
        abort_unless($a->mode === 'Online', 404);
        return $role;
    }
}
