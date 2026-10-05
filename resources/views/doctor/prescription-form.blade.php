@extends('layouts.admin')
@section('title', 'Write prescription')
@section('content')
@php
    $pp = $a->patient->patientProfile;
    $v = old('vitals', $rx['vitals'] ?? []);
    if (! $rx && $pp) { // first time: start from the vitals the patient entered (editable)
        $v = array_filter(['bp' => $pp->bp, 'pulse' => $pp->heart_rate, 'temp' => $pp->temperature, 'spo2' => $pp->spo2, 'weight' => $pp->weight], fn ($x) => filled($x));
    }
    $meds = old('medicines', $rx['medicines'] ?? []);
    if (! $meds) $meds = [['name' => '', 'dosage' => '', 'frequency' => '', 'timing' => '', 'duration' => '', 'notes' => '']];
    $freq = ['1-0-0 (Morning)', '0-1-0 (Afternoon)', '0-0-1 (Night)', '1-0-1 (Morning & Night)', '1-1-1 (Thrice daily)', '1-1-1-1 (Four times)', 'SOS (If needed)', 'Once a week'];
    $timing = ['After food', 'Before food', 'With food', 'Empty stomach', 'At bedtime', 'Any time'];
@endphp
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="page-title">Prescription</h1>
    <div class="d-flex gap-2">
        @if ($a->hasPrescription())<a class="btn btn-outline-primary btn-sm" href="{{ route('prescription.show', $a) }}"><i class="bi bi-eye"></i> View current</a>@endif
        <a class="btn btn-light btn-sm" href="{{ route('doctor.appointments') }}"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</div>

<div class="card fx p-3 mb-3">
    <div class="d-flex flex-wrap gap-4 small">
        <div><span class="text-secondary">Patient</span><div class="fw-semibold">{{ $a->patient->name }} <span class="text-secondary fw-normal">({{ $a->patient->patient_code }})</span></div></div>
        <div><span class="text-secondary">Visit</span><div class="fw-semibold">{{ $a->date->format('d M Y') }}, {{ $a->startsAt()->format('g:i A') }} &middot; {{ $a->mode ?: 'In-person' }}</div></div>
        @if ($a->reason)<div><span class="text-secondary">Reason</span><div class="fw-semibold">{{ $a->reason }}</div></div>@endif
        @if ($pp?->allergies)<div><span class="text-danger">Allergies</span><div class="fw-semibold">{{ $pp->allergies }}</div></div>@endif
    </div>
</div>

<form method="post" action="{{ route('doctor.prescription.save', $a) }}">@csrf @method('PUT')
    <div class="card fx p-3 mb-3">
        <h6 class="mb-3"><i class="bi bi-heart-pulse text-primary"></i> Vitals <span class="text-secondary fw-normal small">(optional)</span></h6>
        <div class="row g-2">
            @foreach (['bp' => 'BP (e.g. 120/80)', 'pulse' => 'Pulse (bpm)', 'temp' => 'Temp (°C)', 'spo2' => 'SpO2 (%)', 'weight' => 'Weight (kg)'] as $k => $l)
                <div class="col-6 col-md"><label class="form-label small fw-semibold">{{ $l }}</label>
                    <input name="vitals[{{ $k }}]" value="{{ $v[$k] ?? '' }}" maxlength="20" class="form-control form-control-sm"></div>
            @endforeach
        </div>
    </div>

    <div class="card fx p-3 mb-3">
        <h6 class="mb-3"><i class="bi bi-clipboard2-pulse text-primary"></i> Clinical notes</h6>
        <div class="mb-3"><label class="form-label small fw-semibold">Chief complaints / symptoms</label>
            <textarea name="complaints" rows="2" maxlength="1000" class="form-control" placeholder="Fever since 2 days, body ache...">{{ old('complaints', $rx['complaints'] ?? '') }}</textarea></div>
        <div><label class="form-label small fw-semibold">Diagnosis</label>
            <input name="diagnosis" maxlength="255" value="{{ old('diagnosis', $rx['diagnosis'] ?? '') }}" class="form-control @error('diagnosis') is-invalid @enderror" placeholder="Viral fever">
            @error('diagnosis')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    </div>

    <div class="card fx p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0"><i class="bi bi-capsule text-primary"></i> Medicines</h6>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addMed"><i class="bi bi-plus-lg"></i> Add medicine</button>
        </div>
        <div id="meds">
            @foreach ($meds as $i => $m)
                <div class="med border rounded-3 p-2 mb-2" data-i="{{ $i }}">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4"><label class="form-label small fw-semibold mb-1">Medicine</label>
                            <input name="medicines[{{ $i }}][name]" value="{{ $m['name'] ?? '' }}" maxlength="120" class="form-control form-control-sm" placeholder="Paracetamol 500 mg tab"></div>
                        <div class="col-6 col-md-2"><label class="form-label small fw-semibold mb-1">Dosage</label>
                            <input name="medicines[{{ $i }}][dosage]" value="{{ $m['dosage'] ?? '' }}" maxlength="60" class="form-control form-control-sm" placeholder="1 tablet"></div>
                        <div class="col-6 col-md-2"><label class="form-label small fw-semibold mb-1">Frequency</label>
                            <input name="medicines[{{ $i }}][frequency]" value="{{ $m['frequency'] ?? '' }}" maxlength="60" list="freqList" class="form-control form-control-sm" placeholder="1-0-1"></div>
                        <div class="col-6 col-md-2"><label class="form-label small fw-semibold mb-1">When</label>
                            <input name="medicines[{{ $i }}][timing]" value="{{ $m['timing'] ?? '' }}" maxlength="40" list="timingList" class="form-control form-control-sm" placeholder="After food"></div>
                        <div class="col-6 col-md-2"><label class="form-label small fw-semibold mb-1">Duration</label>
                            <input name="medicines[{{ $i }}][duration]" value="{{ $m['duration'] ?? '' }}" maxlength="40" class="form-control form-control-sm" placeholder="5 days"></div>
                        <div class="col-10 col-md-11"><input name="medicines[{{ $i }}][notes]" value="{{ $m['notes'] ?? '' }}" maxlength="150" class="form-control form-control-sm" placeholder="Special instruction (optional)"></div>
                        <div class="col-2 col-md-1 text-end"><button type="button" class="btn btn-sm btn-outline-danger rm" title="Remove"><i class="bi bi-trash"></i></button></div>
                    </div>
                </div>
            @endforeach
        </div>
        <datalist id="freqList">@foreach ($freq as $f)<option value="{{ $f }}">@endforeach</datalist>
        <datalist id="timingList">@foreach ($timing as $t)<option value="{{ $t }}">@endforeach</datalist>
    </div>

    <div class="card fx p-3 mb-3">
        <h6 class="mb-3"><i class="bi bi-journal-medical text-primary"></i> Tests, advice &amp; follow-up</h6>
        <div class="mb-3"><label class="form-label small fw-semibold">Tests / investigations advised</label>
            <textarea name="investigations" rows="2" maxlength="1000" class="form-control" placeholder="CBC, Blood sugar (fasting)...">{{ old('investigations', $rx['investigations'] ?? '') }}</textarea></div>
        <div class="mb-3"><label class="form-label small fw-semibold">Advice</label>
            <textarea name="advice" rows="3" maxlength="1500" class="form-control" placeholder="Drink plenty of fluids, take rest, avoid oily food...">{{ old('advice', $rx['advice'] ?? '') }}</textarea></div>
        <div style="max-width:240px"><label class="form-label small fw-semibold">Follow-up date (optional)</label>
            <input type="date" name="follow_up_date" min="{{ $a->date->toDateString() }}" value="{{ old('follow_up_date', $rx['follow_up_date'] ?? '') }}" class="form-control @error('follow_up_date') is-invalid @enderror">
            @error('follow_up_date')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    </div>

    <button class="btn btn-primary"><i class="bi bi-check2-circle"></i> Save &amp; preview PDF</button>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const box = document.getElementById('meds');
    let n = box.querySelectorAll('.med').length + 100;
    document.getElementById('addMed').addEventListener('click', () => {
        const first = box.querySelector('.med');
        const el = first.cloneNode(true);
        const i = n++;
        el.dataset.i = i;
        el.querySelectorAll('input').forEach(inp => {
            inp.name = inp.name.replace(/medicines\[\d+\]/, 'medicines[' + i + ']');
            inp.value = '';
        });
        box.appendChild(el);
        el.querySelector('input').focus();
    });
    box.addEventListener('click', e => {
        const b = e.target.closest('.rm');
        if (!b) return;
        const rows = box.querySelectorAll('.med');
        if (rows.length === 1) { rows[0].querySelectorAll('input').forEach(i => i.value = ''); return; }
        b.closest('.med').remove();
    });
})();
</script>
@endpush
