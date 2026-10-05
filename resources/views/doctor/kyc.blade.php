@extends('layouts.admin')
@section('title', 'eKYC Verification')
@section('content')
@php
  $st = $doctor->verification_status;
  $editable = in_array($st, ['incomplete', 'rejected']);
  $docs = [['license_document', 'Medical licence certificate'], ['id_document', 'Government photo ID (Aadhaar / PAN / Passport)'], ['hospital_document', 'Hospital proof (appointment letter / ID card / ID badge)']];
@endphp
<h1 class="page-title mb-3">eKYC Verification</h1>

<div class="row g-3">
  <div class="col-lg-8">
    @if($st === 'pending')
      <div class="card"><div class="card-body text-center py-5">
        <div class="d-inline-grid mb-3" style="width:72px;height:72px;border-radius:50%;background:#fff6e0;color:#b56f00;place-items:center;font-size:2rem"><i class="bi bi-hourglass-split"></i></div>
        <h2 class="h4 fw-bold">Verification in progress</h2>
        <p class="text-secondary mb-0">Hello {{ $doctor->user->name }}, our admin team is checking your documents.<br>Your profile appears on the website as soon as you are approved.</p>
        <div class="small text-secondary mt-3">Submitted {{ $doctor->submitted_at?->format('d M Y, g:i A') }}</div>
      </div></div>
    @elseif($st === 'approved')
      <div class="card"><div class="card-body text-center py-5">
        <div class="d-inline-grid mb-3" style="width:72px;height:72px;border-radius:50%;background:#dcf5ea;color:#0e7a52;place-items:center;font-size:2rem"><i class="bi bi-patch-check-fill"></i></div>
        <h2 class="h4 fw-bold">You are verified</h2>
        <p class="text-secondary mb-3">Your profile is {{ $doctor->approved ? 'live on the website' : 'verified but currently hidden by the admin' }}.</p>
        @if($doctor->approved)<a class="btn btn-outline-primary btn-sm" target="_blank" href="{{ route('doctors.show', $doctor) }}"><i class="bi bi-eye"></i> View public page</a>@endif
      </div></div>
    @else
      @if($st === 'rejected')
        <div class="alert alert-danger small"><b>Reason from admin:</b><br>{{ $doctor->rejection_reason }}</div>
      @endif
      <form method="post" action="{{ route('doctor.kyc.submit') }}" enctype="multipart/form-data" data-loading>@csrf
        <div class="card mb-3"><div class="card-header">Profile photo <span class="text-danger">*</span></div><div class="card-body">
          <div class="ad-photo-up">
            <div class="pv" id="pv">@if($doctor->photo_url)<img src="{{ $doctor->photo_url }}" alt="">@else<i class="bi bi-person"></i>@endif</div>
            <div><input type="file" name="photo" id="photo" accept="image/*" class="form-control form-control-sm @error('photo') is-invalid @enderror" @required(!$doctor->photo)>
              <div class="small text-secondary mt-1">A clear face photo is required. JPG or PNG, up to 2 MB. Patients see it on the website.</div>
              @error('photo')<div class="text-danger small">{{ $message }}</div>@enderror</div>
          </div>
        </div></div>

        @if($st === 'rejected')
        <div class="card mb-3"><div class="card-header">Licence &amp; hospital details</div><div class="card-body"><div class="row g-3">
          @foreach ([['license_no', 'Licence no.', 'col-sm-6'], ['license_council', 'Issuing council', 'col-sm-6'], ['license_year', 'Year of registration', 'col-sm-4'], ['hospital_name', 'Hospital name', 'col-sm-8'], ['hospital_address', 'Hospital address', 'col-sm-8'], ['hospital_phone', 'Hospital phone', 'col-sm-4']] as [$n, $l, $c])
            <div class="{{ $c }}"><label class="form-label small fw-semibold">{{ $l }}</label>
              <input name="{{ $n }}" value="{{ old($n, $doctor->$n) }}" class="form-control @error($n) is-invalid @enderror" {{ $n === 'hospital_phone' ? '' : 'required' }}>
              @error($n)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
          @endforeach
        </div></div></div>
        @endif

        <div class="card mb-3"><div class="card-header">Documents <small class="text-secondary fw-normal">(PDF, JPG or PNG, max 4 MB each)</small></div><div class="card-body">
          @foreach ($docs as [$n, $l])
            <div class="mb-3"><label class="form-label small fw-semibold">{{ $l }} <span class="text-danger">*</span></label>
              <input type="file" name="{{ $n }}" accept=".pdf,.jpg,.jpeg,.png" class="form-control @error($n) is-invalid @enderror" @required(!$doctor->$n)>
              @if($doctor->$n)<div class="small text-success mt-1"><i class="bi bi-check-circle-fill"></i> Already uploaded. Choose a file only to replace it.</div>@endif
              @error($n)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
          @endforeach
          <div class="small text-secondary"><i class="bi bi-lock"></i> Documents are private and visible to admins only.</div>
        </div></div>

        <div class="form-check mb-3"><input type="checkbox" name="declaration" value="1" class="form-check-input @error('declaration') is-invalid @enderror" id="decl" @checked(old('declaration'))>
          <label for="decl" class="form-check-label small">I confirm the details and documents are genuine. I understand that false information will lead to rejection and removal.</label>
          @error('declaration')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <button type="submit" class="btn btn-primary w-100 py-2"><i class="bi bi-shield-check"></i> {{ $st === 'rejected' ? 'Resubmit for verification' : 'Submit for verification' }}</button>
      </form>
    @endif
  </div>

  <div class="col-lg-4">
    <div class="card"><div class="card-header">How it works</div><div class="card-body small">
      @foreach ([['Create account', true], ['Verify email (OTP)', true], ['Upload photo + documents', $st !== 'incomplete'], ['Admin reviews', $st === 'approved'], ['Live on website', $st === 'approved' && $doctor->approved]] as [$t, $done])
        <div class="d-flex align-items-center gap-2 py-1"><i class="bi bi-{{ $done ? 'check-circle-fill text-success' : 'circle text-secondary' }}"></i> {{ $t }}</div>
      @endforeach
    </div></div>
  </div>
</div>
<script>
  (function(){var ph=document.getElementById('photo'),pv=document.getElementById('pv');
  if(ph&&pv)ph.addEventListener('change',function(){var f=ph.files[0];if(!f)return;var r=new FileReader();r.onload=function(e){pv.innerHTML='<img src="'+e.target.result+'" alt="">'};r.readAsDataURL(f)})})();
</script>
@endsection
