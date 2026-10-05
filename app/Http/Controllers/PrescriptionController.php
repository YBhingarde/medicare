<?php
namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Doctor writes a structured prescription; doctor, patient and admin can view it and download it as a MediCare PDF. */
class PrescriptionController extends Controller
{
    /** Doctor: only for their own confirmed / completed appointments. */
    private function ownAppointment(Request $r, Appointment $a): Appointment
    {
        $doc = $r->user()->doctor;
        abort_unless($doc && $a->doctor_id === $doc->id, 403);
        abort_unless(in_array($a->status, ['confirmed', 'in_progress', 'completed']), 403, 'A prescription can be written only for confirmed or completed appointments.');
        return $a;
    }

    /** Admin, the doctor of the visit, or the patient of the visit. */
    private function readable(Request $r, Appointment $a): Appointment
    {
        $u = $r->user();
        $ok = $u->role === 'admin'
            || ($u->role === 'doctor' && $u->doctor && $u->doctor->id === $a->doctor_id)
            || ($u->role === 'patient' && $u->id === $a->patient_id);
        abort_unless($ok, 403);
        abort_unless($a->hasPrescription(), 404, 'No prescription has been written for this appointment yet.');
        return $a;
    }

    public function edit(Request $r, Appointment $appointment)
    {
        $a = $this->ownAppointment($r, $appointment)->load('patient.patientProfile', 'doctor.user');
        return view('doctor.prescription-form', ['a' => $a, 'rx' => $a->prescription_data ?? []]);
    }

    public function save(Request $r, Appointment $appointment)
    {
        $a = $this->ownAppointment($r, $appointment);
        $d = $r->validate([
            'complaints' => 'nullable|string|max:1000',
            'diagnosis' => 'nullable|string|max:255',
            'vitals' => 'nullable|array',
            'vitals.*' => 'nullable|string|max:20',
            'medicines' => 'nullable|array|max:20',
            'medicines.*.name' => 'nullable|string|max:120',
            'medicines.*.dosage' => 'nullable|string|max:60',
            'medicines.*.frequency' => 'nullable|string|max:60',
            'medicines.*.timing' => 'nullable|string|max:40',
            'medicines.*.duration' => 'nullable|string|max:40',
            'medicines.*.notes' => 'nullable|string|max:150',
            'investigations' => 'nullable|string|max:1000',
            'advice' => 'nullable|string|max:1500',
            'follow_up_date' => 'nullable|date|after_or_equal:' . $a->date->toDateString(),
        ]);

        $medicines = collect($d['medicines'] ?? [])
            ->map(fn ($m) => collect(['name', 'dosage', 'frequency', 'timing', 'duration', 'notes'])
                ->mapWithKeys(fn ($f) => [$f => trim((string) ($m[$f] ?? ''))])->all())
            ->filter(fn ($m) => $m['name'] !== '')->values()->all();

        $data = [
            'complaints' => trim((string) ($d['complaints'] ?? '')),
            'diagnosis' => trim((string) ($d['diagnosis'] ?? '')),
            'vitals' => collect($d['vitals'] ?? [])->map(fn ($v) => trim((string) $v))->filter()->all(),
            'medicines' => $medicines,
            'investigations' => trim((string) ($d['investigations'] ?? '')),
            'advice' => trim((string) ($d['advice'] ?? '')),
            'follow_up_date' => $d['follow_up_date'] ?? null,
        ];

        if (! $data['diagnosis'] && ! $medicines && ! $data['advice'] && ! $data['investigations']) {
            return back()->withInput()->withErrors(['diagnosis' => 'Add at least a diagnosis, a medicine, a test or advice before saving.']);
        }

        // plain-text copy, so the old lists / dashboards that show "prescription" keep working
        $lines = [];
        if ($data['diagnosis']) $lines[] = 'Diagnosis: ' . $data['diagnosis'];
        foreach ($medicines as $i => $m) {
            $detail = implode(' | ', array_filter([$m['dosage'], $m['frequency'], $m['timing'], $m['duration']]));
            $lines[] = ($i + 1) . '. ' . $m['name'] . ($detail !== '' ? ' - ' . $detail : '');
        }
        if ($data['advice']) $lines[] = 'Advice: ' . $data['advice'];

        $a->update([
            'prescription_data' => $data,
            'prescription' => Str::limit(implode("\n", $lines), 1990, ''),
            'prescription_at' => now(),
        ]);

        return redirect()->route('prescription.show', $a)->with('ok', 'Prescription saved. The patient can now view and download the PDF.');
    }

    /** Printable A4 preview (works even before the PDF package is installed: use Print -> Save as PDF). */
    public function show(Request $r, Appointment $appointment)
    {
        $a = $this->readable($r, $appointment);
        return view('prescriptions.show', $this->data($a) + ['pdfReady' => class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)]);
    }

    public function pdf(Request $r, Appointment $appointment)
    {
        $a = $this->readable($r, $appointment);
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return redirect()->route('prescription.show', $a)->withErrors(['pdf' => 'PDF package is not installed yet. Run: composer require barryvdh/laravel-dompdf  (until then use the Print button and choose "Save as PDF").']);
        }
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('prescriptions.pdf', $this->data($a))->setPaper('a4');
        $name = 'Prescription-' . $a->rxNumber() . '-' . Str::slug($a->patient->name) . '.pdf';
        return $r->boolean('inline') ? $pdf->stream($name) : $pdf->download($name);
    }

    private function data(Appointment $a): array
    {
        $a->load('doctor.user', 'doctor.speciality', 'doctor.hospital', 'doctor.city', 'patient.patientProfile');
        $logoFile = public_path('img/medicare-logo.png');
        return [
            'a' => $a,
            'rx' => $a->prescription_data ?? [],
            'doctor' => $a->doctor,
            'patient' => $a->patient,
            'profile' => $a->patient->patientProfile,
            'logo' => is_file($logoFile) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoFile)) : null,
        ];
    }
}
