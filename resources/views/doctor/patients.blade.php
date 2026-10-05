@extends('layouts.admin')
@section('title','My Patients')
@section('content')
<h1 class="page-title mb-3">My Patients <span class="text-secondary fw-normal">({{ $rows->count() }})</span></h1>
<div class="card fx"><div class="table-responsive"><table class="table align-middle">
<thead><tr><th>Patient</th><th>Phone</th><th>Visits</th><th>Completed</th><th>Last appointment</th></tr></thead><tbody>
@forelse($rows as $x)<tr><td><div class="d-flex align-items-center gap-2"><span class="avatar-sm">{{ $x['patient']->initials }}</span><div>{{ $x['patient']->name }}<div class="small text-secondary">{{ $x['patient']->email }}</div></div></div></td>
  <td>{{ $x['patient']->phone ?: '-' }}</td><td><span class="tag tag-indigo">{{ $x['visits'] }}</span></td><td>{{ $x['completed'] }}</td><td>{{ $x['last']->format('d M Y') }}</td></tr>
@empty<tr><td colspan="5" class="empty">No patients yet.</td></tr>@endforelse
</tbody></table></div></div>
@endsection
