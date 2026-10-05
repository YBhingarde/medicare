@extends('layouts.admin')
@section('title','Doctors')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div class="d-flex align-items-center gap-2"><h1 class="page-title mb-0">Doctors</h1><span class="badge badge-soft">Total Doctors : {{ $doctors->count() }}</span></div>
  <div class="d-flex gap-2">
    @php $pend = $doctors->where('verification_status','pending')->count(); @endphp
    <a class="btn btn-outline-primary" href="{{ route('admin.doctors.verifications') }}"><i class="bi bi-shield-check"></i> Verification @if($pend)<span class="badge rounded-pill text-bg-danger">{{ $pend }}</span>@endif</a>
    <a class="btn btn-primary" href="{{ route('admin.doctors.create') }}"><i class="bi bi-plus-lg"></i> New Doctor</a></div></div>
<div class="row g-3">
@forelse($doctors as $d)
  <div class="col-md-6 col-xl-4 mc-reveal">
    <div class="ad-grid-card">
      <div class="ad-photo">@include('partials.doc-photo', ['d' => $d, 'cls' => 'sm'])</div>
      <div class="flex-grow-1 min-w-0">
        <div class="d-flex justify-content-between gap-2">
          <div><div class="fw-bold">{{ $d->user->name }}</div><div class="text-secondary small">{{ $d->designation ?: $d->speciality->name }}</div></div>
          @if($d->verification_status==='pending')<span class="status status-pending align-self-start">Pending</span>
          @elseif($d->verification_status==='incomplete')<span class="status status-pending align-self-start">eKYC awaited</span>
          @elseif($d->verification_status==='rejected')<span class="status status-cancelled align-self-start">Rejected</span>
          @else<span class="status {{ $d->approved?'status-completed':'status-pending' }} align-self-start">{{ $d->approved?'Live':'Hidden' }}</span>@endif
        </div>
        <div class="small text-secondary mt-1">{{ $d->speciality->name }} &middot; {{ $d->city->name }} &middot; {{ $d->experience_years }} yrs</div>
        <div class="small mt-1">Starts from : <b class="text-primary">&#8377;{{ number_format($d->fees) }}</b></div>
        <div class="d-flex flex-wrap gap-1 mt-2">
          <a class="btn btn-sm btn-outline-primary" href="{{ route('doctors.show',$d) }}" target="_blank" title="View on website"><i class="bi bi-eye"></i></a>
          <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.doctors.edit',$d) }}"><i class="bi bi-pencil"></i> Edit</a>
          @if($d->verification_status==='approved')
          <form class="d-inline" method="post" action="{{ route('admin.doctors.toggle',$d) }}">@csrf @method('PATCH')
            <button class="btn btn-sm btn-outline-primary">{{ $d->approved?'Hide':'Show' }}</button></form>
          @elseif($d->verification_status!=='incomplete')<a class="btn btn-sm btn-primary" href="{{ route('admin.doctors.verify',$d) }}"><i class="bi bi-shield-check"></i> Review</a>@endif
          <form class="d-inline" method="post" action="{{ route('admin.doctors.destroy',$d) }}" onsubmit="return confirm('Delete this doctor and all their appointments?')">@csrf @method('DELETE')
            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
        </div>
      </div>
    </div>
  </div>
@empty
  <div class="col-12"><div class="card p-5 text-center text-secondary">No doctors yet. Add the first one.</div></div>
@endforelse
</div>
@push('scripts')<script src="{{ asset('js/site.js') }}"></script>@endpush
@endsection
