@extends('layouts.admin')
@section('title', $doctor->user->name)
@section('content')
<div class="d-flex align-items-center gap-2 mb-3"><a href="{{ route('admin.doctors') }}" class="text-decoration-none text-secondary"><i class="bi bi-chevron-left"></i> Doctors</a></div>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h1 class="page-title mb-0">Doctor Details</h1>
  <div class="d-flex gap-2">
    @if($doctor->approved)<a class="btn btn-outline-primary btn-sm" target="_blank" href="{{ route('doctors.show',$doctor) }}"><i class="bi bi-box-arrow-up-right"></i> View on website</a>@endif
    <a class="btn btn-primary btn-sm" href="{{ route('admin.doctors.edit',$doctor) }}"><i class="bi bi-pencil"></i> Edit</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card"><div class="card-body text-center">
      <div class="dd-photo mx-auto mb-3">@include('partials.doc-photo', ['d' => $doctor])</div>
      <h2 class="fs-5 fw-bold mb-0">{{ $doctor->user->name }}</h2>
      <div class="text-secondary">{{ $doctor->designation ?: $doctor->speciality->name }}</div>
      <span class="status {{ $doctor->approved?'status-completed':'status-pending' }} mt-2 d-inline-block">{{ $doctor->approved?'Live on website':'Hidden' }}</span>
      <div class="row g-2 mt-3">
        <div class="col-4"><div class="mini"><b>{{ $total }}</b>Appts</div></div>
        <div class="col-4"><div class="mini"><b>{{ $patients }}</b>Patients</div></div>
        <div class="col-4"><div class="mini"><b>{{ $completed }}</b>Done</div></div>
      </div>
      <div class="text-start mt-3">
        <div class="avail-row"><span class="text-secondary">Email</span><b class="text-break ms-2 text-end">{{ $doctor->user->email }}</b></div>
        <div class="avail-row"><span class="text-secondary">Phone</span><b>{{ $doctor->user->phone ?: '-' }}</b></div>
        <div class="avail-row"><span class="text-secondary">Speciality</span><b>{{ $doctor->speciality->name }}</b></div>
        <div class="avail-row"><span class="text-secondary">City</span><b>{{ $doctor->city->name }}</b></div>
        <div class="avail-row"><span class="text-secondary">Experience</span><b>{{ $doctor->experience_years }} yrs</b></div>
        <div class="avail-row"><span class="text-secondary">Fees</span><b>&#8377;{{ number_format($doctor->fees) }}</b></div>
        <div class="avail-row"><span class="text-secondary">Slot</span><b>{{ $doctor->slot_minutes }} min</b></div>
        <div class="avail-row"><span class="text-secondary">Type</span><b>{{ $doctor->appointment_type }}</b></div>
        @if($doctor->license_no)<div class="avail-row"><span class="text-secondary">Licence</span><b>{{ $doctor->license_no }}</b></div>@endif
        @if($doctor->gender)<div class="avail-row"><span class="text-secondary">Gender</span><b>{{ $doctor->gender }}</b></div>@endif
        @if($doctor->blood_group)<div class="avail-row"><span class="text-secondary">Blood group</span><b>{{ $doctor->blood_group }}</b></div>@endif
        @if($doctor->dob)<div class="avail-row"><span class="text-secondary">DOB</span><b>{{ $doctor->dob->format('d M Y') }}</b></div>@endif
      </div>
    </div></div>
  </div>

  <div class="col-lg-8">
    <div class="card mb-3"><div class="card-header">About</div><div class="card-body">
      <p class="mb-2">{{ $doctor->about ?: 'No bio added yet.' }}</p>
      @if($doctor->qualification)<div class="small"><span class="text-secondary">Qualification:</span> {{ $doctor->qualification }}</div>@endif
      @if($doctor->address)<div class="small"><span class="text-secondary">Address:</span> {{ $doctor->address }}, {{ $doctor->city->name }}</div>@endif
      @if(!empty($doctor->languages))<div class="mt-2">@foreach($doctor->languages as $l)<span class="tag tag-indigo me-1">{{ $l }}</span>@endforeach</div>@endif
    </div></div>

    <div class="card mb-3"><div class="card-header">Weekly availability</div><div class="card-body">
      @php $av = $doctor->availability ?? []; @endphp
      @foreach(\App\Models\Doctor::DAYS as $k => $name)
        @php $wins = empty($av) ? [[$doctor->slot_start, $doctor->slot_end]] : ($av[$k] ?? []); @endphp
        <div class="avail-row"><span class="text-secondary">{{ $name }}</span>
          <b class="text-end">@forelse($wins as $w){{ \Carbon\Carbon::parse($w[0])->format('g:i A') }} - {{ \Carbon\Carbon::parse($w[1])->format('g:i A') }}@if(!$loop->last), @endif @empty<span class="text-secondary fw-normal">Day off</span>@endforelse</b></div>
      @endforeach
    </div></div>

    <div class="row g-3">
      @foreach([['education','Education','mortarboard'],['awards','Awards','award'],['certifications','Certifications','file-earmark-check']] as [$key,$label,$ic])
      <div class="col-md-{{ $key==='education' ? '12' : '6' }}"><div class="card h-100"><div class="card-header"><i class="bi bi-{{ $ic }} text-primary"></i> {{ $label }}</div><div class="card-body pt-2">
        @forelse($doctor->$key ?? [] as $row)
          <div class="list-row"><div>
            <div class="fw-semibold">{{ $row['school'] ?? $row['title'] ?? '' }}@if(!empty($row['degree'])) - {{ $row['degree'] }}@endif</div>
            @if(!empty($row['from']) || !empty($row['to']))<div class="small text-secondary">{{ $row['from'] ?? '' }} - {{ $row['to'] ?? '' }}</div>@endif
            @if(!empty($row['description']))<div class="small text-secondary">{{ $row['description'] }}</div>@endif
          </div></div>
        @empty<div class="empty">Nothing added.</div>@endforelse
      </div></div></div>
      @endforeach
    </div>
  </div>
</div>
@endsection