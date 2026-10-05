@extends('layouts.admin')
@php
  $edit = (bool) $doctor;
  $panel = $panel ?? 'admin';
  $backUrl = $panel === 'doctor' ? route('doctor.dashboard') : route('admin.doctors');
  $backLabel = $panel === 'doctor' ? 'Dashboard' : 'Doctors';
  $formAction = $panel === 'doctor' ? route('doctor.profile.update') : ($edit ? route('admin.doctors.update', $doctor) : route('admin.doctors.store'));
  $v = fn ($f, $d = '') => old($f, $edit ? ($doctor->$f ?? $d) : $d);
  $rowsOf = fn ($f, $blank) => old($f, $edit && ! empty($doctor->$f) ? $doctor->$f : [$blank]);
  $av = old('availability', $edit && $doctor->availability ? $doctor->availability : collect($days)->map(fn ($n, $k) => in_array($k, ['sun']) ? [] : [['10:00', '13:00'], ['17:00', '20:00']])->all());
  $langs = old('languages', $edit ? implode(', ', $doctor->languages ?? []) : 'English, Hindi, Marathi');
@endphp
@section('title', $panel === 'doctor' ? 'My profile' : ($edit ? 'Edit doctor' : 'Add doctor'))
@section('content')
<div class="d-flex align-items-center gap-2 mb-3"><a href="{{ $backUrl }}" class="text-decoration-none text-secondary"><i class="bi bi-chevron-left"></i> {{ $backLabel }}</a></div>
<h1 class="page-title mb-3">{{ $panel === 'doctor' ? 'My profile' : ($edit ? 'Edit doctor' : 'New Doctor') }}</h1>
<form method="post" enctype="multipart/form-data" action="{{ $formAction }}">@csrf @if($edit) @method('PUT') @endif

<div class="ad-sec"><h2>Contact information</h2>
  <div class="ad-photo-up mb-3">
    <div class="pv" id="pv">@if($edit && $doctor->photo_url)<img src="{{ $doctor->photo_url }}" alt="">@else<i class="bi bi-person"></i>@endif</div>
    <div><label class="form-label mb-1">Profile image <span class="text-danger">*</span></label><input type="file" name="photo" id="photo" accept="image/*" class="form-control form-control-sm" @required(!($edit && $doctor->photo))>
      <div class="small text-secondary mt-1">Required. JPG or PNG, up to 2 MB. Shown on the website.</div>@error('photo')<div class="text-danger small">{{ $message }}</div>@enderror</div>
  </div>
  <div class="row g-3">
    <div class="col-md-6"><label class="form-label">Name *</label><input name="name" value="{{ $v('name', $edit ? $doctor->user->name : '') }}" class="form-control" required></div>
    <div class="col-md-6"><label class="form-label">Email (login) *</label><input type="email" name="email" value="{{ old('email', $edit ? $doctor->user->email : '') }}" class="form-control" required></div>
    <div class="col-md-6"><label class="form-label">Phone number</label><input name="phone" value="{{ old('phone', $edit ? $doctor->user->phone : '') }}" class="form-control"></div>
    <div class="col-md-6"><label class="form-label">Password {{ $edit ? '(leave blank to keep)' : '*' }}</label><input type="password" name="password" class="form-control" {{ $edit ? '' : 'required' }}></div>
    <div class="col-md-4"><label class="form-label">DOB</label><input type="date" name="dob" value="{{ old('dob', $edit && $doctor->dob ? $doctor->dob->format('Y-m-d') : '') }}" class="form-control"></div>
    <div class="col-md-4"><label class="form-label">Gender</label><select name="gender" class="form-select"><option value="">Select</option>@foreach(['Male','Female','Other'] as $g)<option @selected($v('gender')===$g)>{{ $g }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label">Blood group</label><select name="blood_group" class="form-select"><option value="">Select</option>@foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g)<option @selected($v('blood_group')===$g)>{{ $g }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Speciality (department) *</label><select name="speciality_id" class="form-select" required>@foreach($specialities as $s)<option value="{{ $s->id }}" @selected($v('speciality_id')==$s->id)>{{ $s->name }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Designation</label><input name="designation" value="{{ $v('designation') }}" class="form-control" placeholder="e.g. Senior Consultant"></div>
    <div class="col-md-6"><label class="form-label">Qualification</label><input name="qualification" value="{{ $v('qualification') }}" class="form-control" placeholder="MBBS, MD (Cardiology)"></div>
    <div class="col-md-3"><label class="form-label">Year of experience *</label><input type="number" name="experience_years" value="{{ $v('experience_years', 0) }}" class="form-control" required></div>
    <div class="col-md-3"><label class="form-label">Medical licence number</label><input name="license_no" value="{{ $v('license_no') }}" class="form-control"></div>
    <div class="col-12"><label class="form-label">Languages spoken (comma separated)</label><input name="languages" value="{{ $langs }}" class="form-control"></div>
    <div class="col-12"><label class="form-label">Bio</label><textarea name="about" rows="3" class="form-control" placeholder="About doctor">{{ $v('about') }}</textarea></div>
  </div>
</div>

<div class="ad-sec"><h2>Address information</h2>
  <div class="row g-3">
    <div class="col-md-6"><label class="form-label">City *</label><select name="city_id" class="form-select" required>@foreach($cities as $c)<option value="{{ $c->id }}" @selected($v('city_id')==$c->id)>{{ $c->name }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Address</label><input name="address" value="{{ $v('address') }}" class="form-control"></div>
  </div>
</div>

<div class="ad-sec" id="availability"><h2>Availability</h2>
  @foreach($days as $k => $name)
    <div class="ad-day"><div class="fw-semibold pt-1">{{ $name }}</div>
      <div>
      <div data-wins="{{ $k }}">
        @foreach(($av[$k] ?? []) as $i => $w)
          <div class="ad-win"><input type="time" class="form-control form-control-sm" name="availability[{{ $k }}][{{ $i }}][from]" value="{{ is_array($w) && isset($w['from']) ? $w['from'] : $w[0] }}"><span>to</span><input type="time" class="form-control form-control-sm" name="availability[{{ $k }}][{{ $i }}][to]" value="{{ is_array($w) && isset($w['to']) ? $w['to'] : $w[1] }}"><button type="button" class="btn btn-sm btn-outline-danger" data-rm-win><i class="bi bi-x"></i></button></div>
        @endforeach
      </div>
      <button type="button" class="btn btn-sm btn-outline-primary" data-add-win="{{ $k }}"><i class="bi bi-plus"></i> Add session</button>
      <span class="small text-secondary ms-1" data-off="{{ $k }}"></span>
    </div></div>
  @endforeach
  <div class="small text-secondary mt-2">A day with no session is shown as "not available" on the website.</div>
</div>

<div class="ad-sec"><h2>Appointment information</h2>
  <div class="row g-3">
    <div class="col-md-3"><label class="form-label">Appointment type</label><select name="appointment_type" class="form-select">@foreach(['Clinic','Online','Both'] as $t)<option @selected($v('appointment_type','Clinic')===$t)>{{ $t }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">Accept bookings (in advance)</label><div class="input-group"><input type="number" name="advance_days" value="{{ $v('advance_days', 30) }}" class="form-control" min="1"><span class="input-group-text">Days</span></div></div>
    <div class="col-md-3"><label class="form-label">Appointment duration</label><select name="slot_minutes" class="form-select">@foreach([10,15,20,30,45,60] as $m)<option value="{{ $m }}" @selected($v('slot_minutes',20)==$m)>{{ $m }} mins</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">Patients per slot</label><div class="input-group"><input type="number" name="slot_capacity" value="{{ $v('slot_capacity', 1) }}" class="form-control" min="1" max="20" required><span class="input-group-text">patients</span></div><div class="form-text">When a slot reaches this number it shows as Full.</div></div>
    <div class="col-md-3"><label class="form-label">Consultation charge *</label><div class="input-group"><span class="input-group-text">&#8377;</span><input type="number" name="fees" value="{{ $v('fees', 500) }}" class="form-control" required></div></div>
  </div>
</div>

@foreach([['education','Education information',['school'=>'School / college','degree'=>'Degree','from'=>'From (year)','to'=>'To (year)']],['awards','Awards & recognition',['title'=>'Title','description'=>'Description']],['certifications','Certifications',['title'=>'Title','description'=>'Description']]] as [$key,$label,$fields])
<div class="ad-sec"><h2>{{ $label }}</h2>
  <div data-dyn="{{ $key }}">
    @foreach($rowsOf($key, array_fill_keys(array_keys($fields), '')) as $i => $row)
      <div class="ad-dyn-row"><div class="row g-2">
        @foreach($fields as $f => $ph)<div class="{{ count($fields) > 2 ? 'col-md-3' : 'col-md-6' }}"><input name="{{ $key }}[{{ $i }}][{{ $f }}]" value="{{ $row[$f] ?? '' }}" class="form-control form-control-sm" placeholder="{{ $ph }}"></div>@endforeach
      </div><button type="button" class="btn btn-sm btn-link text-danger px-0 mt-1" data-rm-row><i class="bi bi-trash"></i> Remove</button></div>
    @endforeach
  </div>
  <button type="button" class="btn btn-sm btn-outline-primary" data-add-row="{{ $key }}" data-fields="{{ json_encode(array_keys($fields)) }}" data-ph="{{ json_encode(array_values($fields)) }}" data-col="{{ count($fields) > 2 ? 'col-md-3' : 'col-md-6' }}"><i class="bi bi-plus"></i> Add more</button>
</div>
@endforeach

<div class="d-flex gap-2 mb-4"><button class="btn btn-primary px-4">{{ $edit ? 'Save changes' : 'Save doctor' }}</button><a class="btn btn-light" href="{{ $backUrl }}">Cancel</a></div>
</form>

@push('scripts')
<script>
(function(){
  // photo preview
  var ph=document.getElementById('photo');
  ph.addEventListener('change',function(){var f=ph.files[0];if(!f)return;var r=new FileReader();r.onload=function(e){document.getElementById('pv').innerHTML='<img src="'+e.target.result+'">'};r.readAsDataURL(f)});
  // availability sessions
  function reindex(day){[].forEach.call(document.querySelectorAll('[data-wins="'+day+'"] .ad-win'),function(w,i){var n=w.querySelectorAll('input');n[0].name='availability['+day+']['+i+'][from]';n[1].name='availability['+day+']['+i+'][to]'});
    document.querySelector('[data-off="'+day+'"]').textContent=document.querySelector('[data-wins="'+day+'"] .ad-win')?'':'Day off'}
  document.querySelectorAll('[data-add-win]').forEach(function(b){b.addEventListener('click',function(){var d=b.dataset.addWin,box=document.querySelector('[data-wins="'+d+'"]'),el=document.createElement('div');el.className='ad-win';
    el.innerHTML='<input type="time" class="form-control form-control-sm" value="10:00"><span>to</span><input type="time" class="form-control form-control-sm" value="13:00"><button type="button" class="btn btn-sm btn-outline-danger" data-rm-win><i class="bi bi-x"></i></button>';box.appendChild(el);reindex(d)})});
  document.addEventListener('click',function(e){var x=e.target.closest('[data-rm-win]');if(x){var box=x.closest('[data-wins]'),d=box.dataset.wins;x.parentNode.remove();reindex(d)}
    var r=e.target.closest('[data-rm-row]');if(r){var wrap=r.closest('[data-dyn]');r.closest('.ad-dyn-row').remove();renum(wrap)}});
  document.querySelectorAll('[data-wins]').forEach(function(b){reindex(b.dataset.wins)});
  // education / awards / certifications rows
  function renum(wrap){var k=wrap.dataset.dyn;[].forEach.call(wrap.querySelectorAll('.ad-dyn-row'),function(row,i){[].forEach.call(row.querySelectorAll('input'),function(inp){inp.name=inp.name.replace(/\[\d+\]/,'['+i+']')})})}
  document.querySelectorAll('[data-add-row]').forEach(function(b){b.addEventListener('click',function(){var k=b.dataset.addRow,f=JSON.parse(b.dataset.fields),p=JSON.parse(b.dataset.ph),wrap=document.querySelector('[data-dyn="'+k+'"]'),i=wrap.querySelectorAll('.ad-dyn-row').length,row=document.createElement('div');row.className='ad-dyn-row';
    row.innerHTML='<div class="row g-2">'+f.map(function(n,j){return '<div class="'+b.dataset.col+'"><input name="'+k+'['+i+']['+n+']" class="form-control form-control-sm" placeholder="'+p[j]+'"></div>'}).join('')+'</div><button type="button" class="btn btn-sm btn-link text-danger px-0 mt-1" data-rm-row><i class="bi bi-trash"></i> Remove</button>';wrap.appendChild(row)})});
})();
</script>
@endpush
@endsection