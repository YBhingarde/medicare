@extends('layouts.auth')
@section('title','Doctor registration')
@section('headline','Join MediCare as a verified doctor.')
@section('content')
<style>.mc-auth-box{max-width:560px}.mc-auth-main{align-items:start}.mc-auth .mc-sec{font-weight:700;font-size:.8rem;text-transform:uppercase;letter-spacing:.05em;color:var(--pc);margin:1.4rem 0 .8rem}</style>
<h1 class="fw-bold mb-1" style="font-size:1.7rem">Doctor registration</h1>
<p class="text-secondary mb-2">Create your account in a minute. Patients see you only after our team verifies your licence and hospital, which keeps fake doctors out.</p>
<form method="post" action="{{ route('register.doctor.store') }}" enctype="multipart/form-data" data-loading>@csrf

  <div class="mc-sec">Account</div>
  @foreach([['name','Full name (as on licence)','text','person'],['email','Email address','email','envelope'],['phone','Mobile number','text','phone'],['password','Password (min 8 characters)','password','lock'],['password_confirmation','Confirm password','password','shield-lock']] as [$n,$l,$t,$ic])
  <div class="mc-field"><i class="bi bi-{{ $ic }} lead-ic"></i>
    <input type="{{ $t }}" name="{{ $n }}" placeholder="{{ $l }}" value="{{ $t==='password' ? '' : old($n) }}" class="form-control @error($n) is-invalid @enderror" required>
    @if($t==='password')<button type="button" class="eye" aria-label="Show password"><i class="bi bi-eye"></i></button>@endif
    @error($n)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
  @endforeach

  <div class="mc-sec">Professional details</div>
  <div class="row g-3 mb-3">
    <div class="col-sm-6"><label class="form-label small fw-semibold">Speciality</label>
      <select name="speciality_id" class="form-select @error('speciality_id') is-invalid @enderror" required><option value="">Select</option>@foreach($specialities as $s)<option value="{{ $s->id }}" @selected(old('speciality_id')==$s->id)>{{ $s->name }}</option>@endforeach</select>@error('speciality_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-sm-6"><label class="form-label small fw-semibold">City</label>
      <select name="city_id" class="form-select @error('city_id') is-invalid @enderror" required><option value="">Select</option>@foreach($cities as $c)<option value="{{ $c->id }}" @selected(old('city_id')==$c->id)>{{ $c->name }}</option>@endforeach</select>@error('city_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-sm-6"><label class="form-label small fw-semibold">Qualification</label><input name="qualification" value="{{ old('qualification') }}" placeholder="MBBS, MD (Cardiology)" class="form-control @error('qualification') is-invalid @enderror" required>@error('qualification')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-sm-3"><label class="form-label small fw-semibold">Experience (yrs)</label><input type="number" min="0" name="experience_years" value="{{ old('experience_years') }}" class="form-control @error('experience_years') is-invalid @enderror" required>@error('experience_years')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-sm-3"><label class="form-label small fw-semibold">Fees (&#8377;)</label><input type="number" min="0" name="fees" value="{{ old('fees') }}" class="form-control @error('fees') is-invalid @enderror" required>@error('fees')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
  </div>

  <div class="mc-sec">Medical licence</div>
  <div class="row g-3 mb-3">
    <div class="col-sm-6"><label class="form-label small fw-semibold">Licence / registration no.</label><input name="license_no" value="{{ old('license_no') }}" class="form-control @error('license_no') is-invalid @enderror" required>@error('license_no')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-sm-6"><label class="form-label small fw-semibold">Issuing council</label><input name="license_council" value="{{ old('license_council') }}" placeholder="e.g. Maharashtra Medical Council" class="form-control @error('license_council') is-invalid @enderror" required>@error('license_council')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-sm-6"><label class="form-label small fw-semibold">Year of registration</label><input type="number" name="license_year" min="1950" max="{{ date('Y') }}" value="{{ old('license_year') }}" class="form-control @error('license_year') is-invalid @enderror" required>@error('license_year')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
  </div>

  <div class="mc-sec">Hospital / clinic</div>
  <div class="row g-3 mb-3">
    <div class="col-12"><label class="form-label small fw-semibold">Hospital or clinic name</label><input name="hospital_name" list="hospitalList" value="{{ old('hospital_name') }}" class="form-control @error('hospital_name') is-invalid @enderror" required>
      <datalist id="hospitalList">@foreach($hospitals as $h)<option value="{{ $h }}">@endforeach</datalist>@error('hospital_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-sm-8"><label class="form-label small fw-semibold">Hospital address</label><input name="hospital_address" value="{{ old('hospital_address') }}" class="form-control @error('hospital_address') is-invalid @enderror" required>@error('hospital_address')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-sm-4"><label class="form-label small fw-semibold">Hospital phone</label><input name="hospital_phone" value="{{ old('hospital_phone') }}" class="form-control @error('hospital_phone') is-invalid @enderror">@error('hospital_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
  </div>

  <div class="small text-secondary mb-3"><i class="bi bi-shield-check text-primary"></i> After you verify your email, your dashboard opens. You will upload your documents and photo there (eKYC), and your profile goes live once our team approves it.</div>
  <button type="submit" class="btn btn-primary w-100 mc-btn py-2">Create account</button>
</form>
<p class="small text-secondary mt-4 mb-0 text-center">Already registered? <a href="{{ route('login') }}" class="fw-semibold text-decoration-none">Log in</a> &middot; Patient? <a href="{{ route('register') }}" class="fw-semibold text-decoration-none">Sign up here</a></p>
@endsection
