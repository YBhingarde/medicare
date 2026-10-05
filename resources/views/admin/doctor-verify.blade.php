@extends('layouts.admin')
@section('title', 'Verify ' . $doctor->user->name)
@section('content')
    @php $st = $doctor->verification_status; @endphp
    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="{{ route('admin.doctors.verifications') }}" class="text-decoration-none text-secondary"><i
                class="bi bi-chevron-left"></i> Verification requests</a>
    </div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="page-title mb-0">{{ $doctor->user->name }}</h1>
        <span
            class="status {{ ['pending' => 'status-pending', 'rejected' => 'status-cancelled', 'approved' => 'status-completed', 'incomplete' => 'status-pending'][$st] }}">{{ ['pending' => 'Pending verification', 'rejected' => 'Rejected', 'approved' => 'Verified', 'incomplete' => 'eKYC not submitted'][$st] }}</span>
    </div>

    <div class="mb-3 small">
        @if ($doctor->user->hasVerifiedEmail())
        <span class="text-success"><i class="bi bi-envelope-check-fill"></i> Email verified</span>@else<span
                class="text-danger"><i class="bi bi-envelope-exclamation-fill"></i> Email not verified yet. The doctor must
                enter the OTP we emailed; you can approve only after that.</span>
        @endif
    </div>
    @if ($duplicate)
        <div class="alert alert-warning py-2"><i class="bi bi-exclamation-triangle"></i> Another account
            (<b>{{ $duplicate->user->name }}</b>, {{ $duplicate->user->email }}) already uses this licence number. Check
            carefully before approving.</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-header">Professional details</div>
                <div class="card-body small">
                    @foreach ([['Email', $doctor->user->email], ['Phone', $doctor->user->phone ?: '-'], ['Speciality', $doctor->speciality->name], ['City', $doctor->city->name], ['Qualification', $doctor->qualification ?: '-'], ['Experience', $doctor->experience_years . ' years'], ['Consultation fees', '₹' . number_format($doctor->fees)]] as [$l, $v])
                        <div class="avail-row"><span
                                class="text-secondary">{{ $l }}</span><span>{{ $v }}</span></div>
                    @endforeach
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header">Medical licence</div>
                <div class="card-body small">
                    @foreach ([['Licence / registration no.', $doctor->license_no ?: '-'], ['Issuing council', $doctor->license_council ?: '-'], ['Year of registration', $doctor->license_year ?: '-']] as [$l, $v])
                        <div class="avail-row"><span class="text-secondary">{{ $l }}</span><span
                                class="fw-semibold">{{ $v }}</span></div>
                    @endforeach
                </div>
            </div>
            <div class="card">
                <div class="card-header">Hospital / clinic</div>
                <div class="card-body small">
                    @foreach ([['Hospital name', $doctor->hospital_name ?: '-'], ['Address', $doctor->hospital_address ?: '-'], ['Phone', $doctor->hospital_phone ?: '-']] as [$l, $v])
                        <div class="avail-row"><span class="text-secondary">{{ $l }}</span><span
                                class="fw-semibold text-end">{{ $v }}</span></div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-header">Uploaded documents</div>
                <div class="card-body">
                    @foreach ([['license', 'Medical licence certificate', 'file-earmark-medical', 'license_document'], ['id', 'Government photo ID', 'person-vcard', 'id_document'], ['hospital', 'Hospital proof', 'hospital', 'hospital_document']] as [$t, $l, $ic, $col])
                        <div class="list-row"><span class="ico ico-indigo" style="width:34px;height:34px"><i
                                    class="bi bi-{{ $ic }}"></i></span>
                            <div class="flex-grow-1 fw-semibold">{{ $l }}</div>
                            @if ($doctor->$col)
                                <a class="btn btn-sm btn-outline-primary" target="_blank"
                                    href="{{ route('admin.doctors.document', [$doctor, $t]) }}"><i
                                        class="bi bi-box-arrow-up-right"></i> Open</a>
                            @else<span class="small text-secondary">Not uploaded</span>
                            @endif
                        </div>
                    @endforeach
                    <div class="small text-secondary mt-2"><i class="bi bi-lock"></i> Documents are private and visible to
                        admins only.</div>
                </div>
            </div>

            @if ($st === 'approved' && $doctor->verified_at)
                <div class="card">
                    <div class="card-body small"><i class="bi bi-patch-check-fill text-success"></i> Verified on
                        {{ $doctor->verified_at->format('d M Y, g:i A') }}@if ($doctor->verifier)
                            by {{ $doctor->verifier->name }}
                        @endif.</div>
                </div>
            @elseif($st === 'rejected')
                <div class="card">
                    <div class="card-body small"><b>Rejected.</b> Reason sent to doctor:<div class="mt-1 text-secondary">
                            {{ $doctor->rejection_reason }}</div>
                        <form method="post" action="{{ route('admin.doctors.approve', $doctor) }}" class="mt-3">@csrf
                            <div class="form-check mb-2"><input type="checkbox" name="confirm" value="1"
                                    class="form-check-input" id="c1"><label for="c1" class="form-check-label">I
                                    checked the licence and hospital. Approve anyway.</label></div>
                            <button class="btn btn-sm btn-primary">Approve doctor</button>
                        </form>
                    </div>
                </div>
            @elseif($st === 'pending')
                <div class="card mb-3">
                    <div class="card-header">Approve</div>
                    <div class="card-body">
                        <form method="post" action="{{ route('admin.doctors.approve', $doctor) }}">@csrf
                            <div class="form-check mb-3"><input type="checkbox" name="confirm" value="1"
                                    class="form-check-input" id="c2"><label for="c2"
                                    class="form-check-label small">I checked the licence number with the council, the ID,
                                    and that this doctor works at the hospital above.</label></div>
                            <button class="btn btn-primary w-100"><i class="bi bi-check2-circle"></i> Approve &amp;
                                publish</button>
                        </form>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">Reject</div>
                    <div class="card-body">
                        <form method="post" action="{{ route('admin.doctors.reject', $doctor) }}">@csrf
                            <label class="form-label small">Reason (shown to the doctor)</label>
                            <textarea name="rejection_reason" rows="3" class="form-control mb-2" required
                                placeholder="e.g. Licence number does not match the council record.">{{ old('rejection_reason') }}</textarea>
                            <button class="btn btn-outline-danger w-100"><i class="bi bi-x-circle"></i> Reject</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
