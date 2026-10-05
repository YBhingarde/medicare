@extends('layouts.admin')
@section('title','Doctor Schedule')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div class="d-flex align-items-center gap-2"><h1 class="page-title mb-0">Doctor Schedule</h1><span class="badge badge-soft">Total Doctors : {{ $total }}</span></div>
  <a class="btn btn-primary btn-sm" href="{{ route('admin.doctors.create') }}"><i class="bi bi-plus-lg"></i> Add Doctor</a>
</div>

<form method="get" class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3" id="schForm">
  <div class="d-flex gap-2">
    <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="width:240px" placeholder="Search doctor, phone or department">
    <button class="btn btn-primary btn-sm">Search</button>
    @if(request('q'))<a class="btn btn-light btn-sm" href="{{ route('admin.doctors.schedule') }}">Clear</a>@endif
  </div>
  <div class="d-flex align-items-center gap-2">
    <select name="sort" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
      <option value="recent" @selected(request('sort','recent')==='recent')>Sort By : Recent</option>
      <option value="name" @selected(request('sort')==='name')>Sort By : Name</option>
    </select>
    <select name="per_page" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
      @foreach([10,25,50] as $n)<option value="{{ $n }}" @selected($per===$n)>{{ $n }} / page</option>@endforeach
    </select>
  </div>
</form>

<div class="card fx"><div class="table-responsive"><table class="table align-middle sch-table">
<thead><tr><th>Doctor</th><th>Department</th><th>Phone</th><th>Availability</th><th class="text-end">Action</th></tr></thead><tbody>
@forelse($doctors as $d)
<tr>
  <td><div class="d-flex align-items-center gap-2">
    <div class="sch-ph">@include('partials.doc-photo', ['d' => $d])</div>
    <div><a class="fw-semibold text-decoration-none" href="{{ route('admin.doctors.show',$d) }}">{{ $d->user->name }}</a>
      <div class="small text-secondary">{{ $d->designation ?: $d->speciality->name }}</div></div></div></td>
  <td class="text-secondary">{{ $d->speciality->name }}</td>
  <td class="text-secondary text-nowrap">{{ $d->user->phone ?: '-' }}</td>
  <td>@include('partials.day-dots', ['d' => $d])</td>
  <td class="text-end text-nowrap">
    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.doctors.edit',$d) }}" title="Edit schedule"><i class="bi bi-pencil-square"></i></a>
    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.doctors.show',$d) }}" title="View details"><i class="bi bi-eye"></i></a></td>
</tr>
@empty
<tr><td colspan="5" class="empty">No doctors found.</td></tr>
@endforelse
</tbody></table></div></div>
<div class="mt-3">{{ $doctors->links('pagination::bootstrap-5') }}</div>
@endsection