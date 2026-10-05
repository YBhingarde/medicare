@php
    $d = $doctor; $p = $profile;
    $age = $p?->dob ? $p->dob->age . ' yrs' : null;
    $vitLabels = ['bp' => 'BP (mmHg)', 'pulse' => 'Pulse (bpm)', 'temp' => 'Temp (°C)', 'spo2' => 'SpO2 (%)', 'weight' => 'Weight (kg)'];
    $vit = collect($vitLabels)->filter(fn ($l, $k) => filled($rx['vitals'][$k] ?? null));
    $meds = $rx['medicines'] ?? [];
    $follow = ! empty($rx['follow_up_date']) ? \Carbon\Carbon::parse($rx['follow_up_date'])->format('d M Y') : null;
@endphp
<div class="rx">
    <div class="top">
        <table><tr>
            <td style="width:60px">@if ($logo)<img src="{{ $logo }}" width="48" height="48" alt="MediCare" style="border-radius:10px">@endif</td>
            <td style="vertical-align:middle"><div class="brand">MediCare</div><div class="tag">Doctor appointments &amp; digital care</div></td>
            <td class="meta"><b>PRESCRIPTION</b><br>No: {{ $a->rxNumber() }}<br>Date: {{ ($a->prescription_at ?? $a->date)->format('d M Y') }}</td>
        </tr></table>
    </div>

    <div class="body">
        <table class="people"><tr>
            <td><div class="box">
                <div class="lbl">Doctor</div>
                <div class="nm">{{ $d->user->name }}</div>
                <div>{{ $d->qualification }}@if ($d->designation) &middot; {{ $d->designation }}@endif</div>
                <div class="muted">{{ $d->speciality->name }}@if ($d->license_no) &middot; Reg. No: {{ $d->license_no }}@endif</div>
                @if ($d->hospital)<div class="muted">{{ $d->hospital->name }}@if ($d->hospital->address), {{ $d->hospital->address }}@endif</div>
                @elseif ($d->address)<div class="muted">{{ $d->address }}</div>@endif
                <div class="muted">{{ $d->city->name }} &middot; {{ $d->user->phone ?: $d->user->email }}</div>
            </div></td>
            <td style="padding-right:0"><div class="box">
                <div class="lbl">Patient</div>
                <div class="nm">{{ $patient->name }}</div>
                <div>ID: {{ $patient->patient_code }}@if ($age) &middot; {{ $age }}@endif @if ($p?->gender) &middot; {{ $p->gender }}@endif @if ($p?->blood_group) &middot; {{ $p->blood_group }}@endif</div>
                <div class="muted">{{ $patient->phone ?: $patient->email }}</div>
                <div class="muted">Visit: {{ $a->date->format('d M Y') }}, {{ $a->startsAt()->format('g:i A') }} &middot; {{ $a->mode ?: 'In-person' }}</div>
                @if ($p?->allergies)<div style="color:#b91c1c"><b>Allergies:</b> {{ \Illuminate\Support\Str::limit($p->allergies, 80) }}</div>@endif
            </div></td>
        </tr></table>

        @if ($vit->isNotEmpty())
            <div class="sec"><h4>Vitals</h4>
                <table class="vit"><tr>
                    @foreach ($vit as $k => $l)<td><div class="k">{{ $l }}</div><div class="v">{{ $rx['vitals'][$k] }}</div></td>@endforeach
                </tr></table>
            </div>
        @endif

        @if (! empty($rx['complaints']))
            <div class="sec"><h4>Chief complaints</h4><div class="pre">{{ $rx['complaints'] }}</div></div>
        @elseif ($a->reason && empty($rx['diagnosis']))
            <div class="sec"><h4>Reason for visit</h4><div>{{ $a->reason }}</div></div>
        @endif
        @if (! empty($rx['diagnosis']))
            <div class="sec"><h4>Diagnosis</h4><div><b>{{ $rx['diagnosis'] }}</b></div></div>
        @endif

        <div class="bigrx">Rx</div>
        @if ($meds)
            <table class="meds">
                <thead><tr><th style="width:4%">#</th><th style="width:30%">Medicine</th><th style="width:13%">Dosage</th><th style="width:15%">Frequency</th><th style="width:14%">When</th><th style="width:11%">Duration</th><th>Notes</th></tr></thead>
                <tbody>
                    @foreach ($meds as $i => $m)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="mn">{{ $m['name'] }}</td>
                            <td>{{ $m['dosage'] ?: '-' }}</td>
                            <td>{{ $m['frequency'] ?: '-' }}</td>
                            <td>{{ $m['timing'] ?: '-' }}</td>
                            <td>{{ $m['duration'] ?: '-' }}</td>
                            <td class="muted">{{ $m['notes'] ?: '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @elseif (empty($rx) && $a->prescription)
            <div class="pre">{{ $a->prescription }}</div>
        @else
            <div class="muted">No medicines prescribed.</div>
        @endif

        @if (! empty($rx['investigations']))
            <div class="sec"><h4>Tests / investigations advised</h4><div class="pre">{{ $rx['investigations'] }}</div></div>
        @endif
        @if (! empty($rx['advice']))
            <div class="sec"><h4>Advice</h4><div class="pre">{{ $rx['advice'] }}</div></div>
        @endif
        @if ($follow)
            <div class="sec"><h4>Follow-up</h4><span class="pill">Next visit on {{ $follow }}</span></div>
        @endif
        @if ($a->mode === 'Online')
            <div class="sec muted">This was an online consultation.</div>
        @endif

        <table class="sign"><tr><td></td><td style="width:230px"><div class="line"><b>{{ $d->user->name }}</b><br><span class="muted">{{ $d->qualification }}@if ($d->license_no) &middot; Reg. {{ $d->license_no }}@endif</span></div></td></tr></table>
    </div>

    <div class="foot">
        This is a computer-generated prescription issued through MediCare (Ref {{ $a->rxNumber() }}). Take medicines only as advised by your doctor. In an emergency visit the nearest hospital.
    </div>
</div>
