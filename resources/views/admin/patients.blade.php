@extends('layouts.admin')
@section('title','Patients')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><h1 class="page-title">Patients <span class="text-secondary fw-normal">({{ $total }})</span></h1>
  <form class="d-flex gap-2" method="get"><input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Name, email or phone"><button class="btn btn-primary btn-sm">Search</button>
  @if(request('q'))<a class="btn btn-light btn-sm" href="{{ route('admin.patients') }}">Clear</a>@endif</form></div>
<div class="card fx"><div class="table-responsive"><table class="table align-middle">
<thead><tr><th>Patient</th><th>Phone</th><th>Appointments</th><th>Last visit</th><th>Joined</th><th></th></tr></thead><tbody>
@forelse($patients as $p)<tr>
  <td><div class="d-flex align-items-center gap-2"><span class="avatar-sm">{{ $p->initials }}</span><div><a class="fw-semibold text-decoration-none" href="{{ route('admin.patients.show',$p) }}">{{ $p->name }}</a><div class="small text-secondary">{{ $p->email }}</div></div></div></td>
  <td>{{ $p->phone ?: '-' }}</td><td><span class="tag tag-indigo">{{ $p->appointments_count }}</span></td>
  <td>{{ $p->appointments_max_date ? \Carbon\Carbon::parse($p->appointments_max_date)->format('d M Y') : '-' }}</td>
  <td>{{ $p->created_at->format('d M Y') }}</td>
  <td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.patients.show',$p) }}">View</a>
    <form class="d-inline" method="post" action="{{ route('admin.patients.destroy',$p) }}" onsubmit="return confirm('Delete this patient and all their appointments?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr>
@empty<tr><td colspan="6" class="empty">No patients found.</td></tr>@endforelse
</tbody></table></div></div>
<div class="mt-3">{{ $patients->links('pagination::bootstrap-5') }}</div>
@endsection
