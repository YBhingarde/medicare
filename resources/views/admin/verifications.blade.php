@extends('layouts.admin')
@section('title','Doctor verification')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h1 class="page-title mb-0">Doctor verification</h1>
  <div class="d-flex gap-2">
    @foreach(['pending'=>'Pending','rejected'=>'Rejected','approved'=>'Verified'] as $k=>$l)
      <a class="btn btn-sm {{ $status===$k ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('admin.doctors.verifications',['status'=>$k]) }}">{{ $l }} ({{ $counts[$k] }})</a>
    @endforeach
  </div>
</div>
<div class="card fx"><div class="table-responsive"><table class="table align-middle">
<thead><tr><th>Doctor</th><th>Licence</th><th>Hospital</th><th>Submitted</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($doctors as $d)
  <tr>
    <td><div class="d-flex align-items-center gap-2"><span class="avatar-sm">{{ $d->user->initials }}</span><div><div class="fw-semibold">{{ $d->user->name }}</div><div class="small text-secondary">{{ $d->speciality->name }} &middot; {{ $d->city->name }}</div></div></div></td>
    <td>{{ $d->license_no ?: '-' }}<div class="small text-secondary">{{ $d->license_council }}</div></td>
    <td>{{ $d->hospital_name ?: '-' }}</td>
    <td class="small">{{ $d->submitted_at?->format('d M Y, g:i A') ?? '-' }}</td>
    <td><span class="status {{ ['pending'=>'status-pending','rejected'=>'status-cancelled','approved'=>'status-completed'][$d->verification_status] }}">{{ ['pending'=>'Pending','rejected'=>'Rejected','approved'=>'Verified'][$d->verification_status] }}</span></td>
    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.doctors.verify',$d) }}"><i class="bi bi-shield-check"></i> {{ $d->verification_status==='pending' ? 'Review' : 'View' }}</a></td>
  </tr>
@empty
  <tr><td colspan="6" class="empty">Nothing here. {{ $status==='pending' ? 'No doctors are waiting for verification.' : '' }}</td></tr>
@endforelse
</tbody></table></div></div>
<div class="mt-3">{{ $doctors->links() }}</div>
@endsection
