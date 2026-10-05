@extends('layouts.admin')
@section('title','My profile')
@section('content')
@php
  $phone = $user->phone;
  $addr = collect([$profile->address, $profile->city, $profile->pincode])->filter()->implode(', ');
  $vitals = [
    ['Blood Pressure', $profile->bp ? $profile->bp.' mmHg' : null, 'droplet'],
    ['Heart Rate', $profile->heart_rate ? $profile->heart_rate.' bpm' : null, 'heart-pulse'],
    ['SPO2', $profile->spo2 ? $profile->spo2.' %' : null, 'lungs'],
    ['Temperature', $profile->temperature !== null ? rtrim(rtrim(number_format($profile->temperature,1),'0'),'.').' °C' : null, 'thermometer-half'],
    ['Respiratory Rate', $profile->respiratory_rate ? $profile->respiratory_rate.' rpm' : null, 'activity'],
    ['Weight', $profile->weight !== null ? rtrim(rtrim(number_format($profile->weight,1),'0'),'.').' kg' : null, 'speedometer2'],
  ];
  $filtered = request()->hasAny(['q','status','from','to']);
  $statuses = $tab === 'transactions' ? ['pending'=>'Pending','paid'=>'Paid','refunded'=>'Refunded','cancelled'=>'Cancelled'] : ['pending'=>'Pending','confirmed'=>'Confirmed','in_progress'=>'In progress','completed'=>'Completed','cancelled'=>'Cancelled','expired'=>'Expired'];
@endphp

<div class="pp-head card fx mb-3"><div class="card-body d-flex flex-wrap align-items-center gap-3">
  <div class="pp-photo">@if($profile->photo_url)<img src="{{ $profile->photo_url }}" alt="{{ $user->name }}">@else<span>{{ $user->initials }}</span>@endif</div>
  <div class="flex-grow-1 min-w-0">
    <div class="small text-secondary">#{{ $user->patient_code }}</div>
    <h1 class="h5 fw-bold mb-1">{{ $user->name }}</h1>
    <div class="small text-secondary mb-1"><i class="bi bi-geo-alt"></i> {{ $addr ?: 'Address not added yet' }}</div>
    <div class="small text-secondary d-flex flex-wrap gap-3">
      <span><i class="bi bi-telephone"></i> Phone : {{ $phone ?: '-' }}</span>
      <span><i class="bi bi-calendar-check"></i> Last Visited : {{ $lastVisit ? $lastVisit->format('d M Y') : 'No visits yet' }}</span>
    </div>
    @if($profile->completion() < 100)<div class="pp-progress mt-2"><div class="small text-secondary mb-1">Profile {{ $profile->completion() }}% complete</div><div class="bar"><i style="width:{{ $profile->completion() }}%"></i></div></div>@endif
  </div>
  <div class="d-flex flex-column gap-2 align-items-end">
    <a class="btn btn-primary btn-sm" href="{{ route('doctors') }}"><i class="bi bi-calendar-plus"></i> Book Appointment</a>
    <a class="btn btn-outline-primary btn-sm" href="{{ route('patient.profile.edit') }}"><i class="bi bi-pencil"></i> Edit profile</a>
  </div>
</div></div>

<div class="row g-3 mb-3">
  <div class="col-lg-5"><div class="card h-100"><div class="card-header"><i class="bi bi-person-lines-fill text-primary me-1"></i> About</div><div class="card-body">
    <div class="pp-grid">
      @foreach([['DOB',$profile->dob?->format('d M Y'),'calendar3'],['Blood Group',$profile->blood_group,'droplet'],['Gender',$profile->gender,'gender-ambiguous'],['Email',$user->email,'envelope']] as [$l,$v,$ic])
        <div class="pp-item"><span class="ico-c"><i class="bi bi-{{ $ic }}"></i></span><div class="min-w-0"><div class="fw-semibold">{{ $l }}</div><div class="text-secondary text-truncate">{{ $v ?: '-' }}</div></div></div>
      @endforeach
    </div>
    <hr class="my-3">
    <div class="avail-row"><span class="text-secondary">Preferred doctor</span>
      <span class="text-end">@if($profile->preferredDoctor)<a class="text-decoration-none fw-semibold" href="{{ route('doctors.show',$profile->preferredDoctor) }}">{{ $profile->preferredDoctor->user->name }}</a><div class="small text-secondary">{{ $profile->preferredDoctor->speciality->name }}</div>@else - @endif</span></div>
    <div class="avail-row"><span class="text-secondary">Emergency contact</span><span class="text-end">{{ $profile->emergency_name ?: '-' }}@if($profile->emergency_phone)<div class="small text-secondary">{{ $profile->emergency_phone }}</div>@endif</span></div>
    <div class="avail-row"><span class="text-secondary">Allergies</span><span class="text-end" style="max-width:60%">{{ $profile->allergies ?: '-' }}</span></div>
  </div></div></div>

  <div class="col-lg-7"><div class="card h-100"><div class="card-header d-flex justify-content-between"><span><i class="bi bi-activity text-primary me-1"></i> Vital Signs</span>
    @if($profile->vitals_updated_at)<span class="small text-secondary fw-normal">Updated {{ $profile->vitals_updated_at->format('d M Y') }}</span>@endif</div><div class="card-body">
    <div class="pp-vitals">
      @foreach($vitals as [$l,$v,$ic])
        <div class="pp-item"><span class="ico-c"><i class="bi bi-{{ $ic }}"></i></span><div><div class="fw-semibold">{{ $l }}</div><div class="text-secondary">{{ $v ?: '-' }}</div></div></div>
      @endforeach
    </div>
    @if($myDoctors->count())
      <hr class="my-3"><div class="fw-semibold mb-1">My Doctors</div>
      <div class="d-flex flex-wrap gap-2">@foreach($myDoctors as $m)<a class="pp-chip" href="{{ route('doctors.show',$m['doctor']) }}"><span class="avatar-sm">{{ $m['doctor']->user->initials }}</span>{{ $m['doctor']->user->name }} <small class="text-secondary">&middot; {{ $m['visits'] }}</small></a>@endforeach</div>
    @endif
  </div></div></div>
</div>

<ul class="pp-tabs">
  <li><a class="{{ $tab==='appointments'?'active':'' }}" href="{{ route('patient.profile') }}">Appointments <span class="n">{{ $counts['appointments'] }}</span></a></li>
  <li><a class="{{ $tab==='transactions'?'active':'' }}" href="{{ route('patient.profile',['tab'=>'transactions']) }}">Transactions <span class="n">{{ $counts['transactions'] }}</span></a></li>
</ul>

@if($tab==='transactions')
<div class="row g-3 mb-3">
  @foreach([['Paid',$totals['paid'],'check2-circle','green'],['Pending',$totals['pending'],'hourglass-split','amber'],['Refunded',$totals['refunded'],'arrow-counterclockwise','blue']] as $i=>[$l,$v,$ic,$c])
  <div class="col-sm-4">@include('partials.stat-card',['label'=>$l,'value'=>$v,'icon'=>$ic,'color'=>$c,'prefix'=>'₹','delay'=>$i*80])</div>@endforeach
</div>
@endif

<form method="get" class="pp-filter mb-3">
  <input type="hidden" name="tab" value="{{ $tab }}">
  <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="{{ $tab==='transactions' ? 'Search doctor or reference' : 'Search doctor' }}">
  <select name="status" class="form-select form-select-sm"><option value="">All status</option>@foreach($statuses as $k=>$l)<option value="{{ $k }}" @selected(request('status')===$k)>{{ $l }}</option>@endforeach</select>
  <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm" title="From date">
  <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm" title="To date">
  <button class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button>
  @if($filtered)<a class="btn btn-light btn-sm" href="{{ route('patient.profile',['tab'=>$tab]) }}">Clear</a>@endif
</form>

<div class="card fx"><div class="table-responsive">
@if($tab==='appointments')
<table class="table align-middle">
  <thead><tr><th>Date &amp; Time</th><th>Doctor Name</th><th>Mode</th><th>Status</th><th></th></tr></thead><tbody>
  @forelse($appointments as $a)
    <tr>
      <td class="text-nowrap">{{ $a->date->format('d M Y') }} - {{ \Carbon\Carbon::parse($a->time)->format('h:i A') }}</td>
      <td><div class="d-flex align-items-center gap-2">@include('partials.doc-photo-sm', ['d' => $a->doctor])<div><a class="fw-semibold text-decoration-none" href="{{ route('doctors.show',$a->doctor) }}">{{ $a->doctor->user->name }}</a><div class="small text-secondary">{{ $a->doctor->speciality->name }}</div></div></div></td>
      <td>{{ $a->mode ?? 'In-person' }}</td>
      <td><span class="status status-{{ $a->status }}">{{ $a->statusLabel() }}</span></td>
      <td class="text-end text-nowrap">
        @if($a->hasPrescription())@include('partials.rx-buttons', ['a' => $a])@endif
        @include('partials.patient-appt-actions', ['a' => $a])
      </td>
    </tr>
  @empty
    <tr><td colspan="5" class="empty">{{ $filtered ? 'No appointments match these filters.' : 'No appointments yet.' }} <a href="{{ route('doctors') }}">Find a doctor</a></td></tr>
  @endforelse
</tbody></table>
@else
<table class="table align-middle">
  <thead><tr><th>Reference</th><th>Date</th><th>Doctor Name</th><th>Amount</th><th>Method</th><th>Status</th><th></th></tr></thead><tbody>
  @forelse($transactions as $t)
    <tr>
      <td class="fw-semibold">{{ $t->reference }}</td>
      <td class="text-nowrap">{{ ($t->paid_at ?? $t->created_at)->format('d M Y') }}<div class="small text-secondary">Visit {{ $t->appointment->date->format('d M') }}</div></td>
      <td><div class="d-flex align-items-center gap-2">@include('partials.doc-photo-sm', ['d' => $t->doctor])<div>{{ $t->doctor->user->name }}<div class="small text-secondary">{{ $t->doctor->speciality->name }}</div></div></div></td>
      <td class="fw-semibold">&#8377;{{ number_format($t->amount) }}</td>
      <td class="small">{{ $t->method }}</td>
      <td><span class="status {{ \App\Models\Transaction::PILL[$t->status] }}">{{ ucfirst($t->status) }}</span></td>
      <td class="text-end">@if($t->awaitingPayment() && $t->appointment->isOpen())<a class="btn btn-sm btn-success" href="{{ route('patient.pay',$t->appointment) }}"><i class="bi bi-credit-card"></i> Pay now</a>@endif @if(in_array($t->status,['paid','refunded']))<a class="btn btn-sm btn-outline-primary" target="_blank" href="{{ route('patient.receipt',$t) }}"><i class="bi bi-receipt"></i> Receipt</a>@endif</td>
    </tr>
  @empty
    <tr><td colspan="7" class="empty">{{ $filtered ? 'No transactions match these filters.' : 'No transactions yet. They appear when you book an appointment.' }}</td></tr>
  @endforelse
</tbody></table>
@endif
</div></div>
<div class="mt-3">{{ ($tab==='appointments' ? $appointments : $transactions)->links() }}</div>
@endsection
