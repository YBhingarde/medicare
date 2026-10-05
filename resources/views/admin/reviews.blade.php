@extends('layouts.admin')
@section('title','Reviews')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><h1 class="page-title">Reviews &amp; Ratings</h1>
  <form method="get" class="d-flex flex-wrap gap-2">
    <select name="doctor" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Doctor"><option value="">All doctors</option>
      @foreach($doctors as $d)<option value="{{ $d->id }}" @selected(request('doctor')==$d->id)>{{ $d->user->name }}</option>@endforeach</select>
    <select name="stars" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Stars"><option value="">All ratings</option>
      @foreach([5,4,3,2,1] as $s)<option value="{{ $s }}" @selected(request('stars')==$s)>{{ $s }} star{{ $s>1?'s':'' }}</option>@endforeach</select>
    @if(request()->hasAny(['doctor','stars']))<a class="btn btn-light btn-sm" href="{{ route('admin.reviews') }}">Clear</a>@endif</form></div>
<div class="row g-3">
  <div class="col-lg-4"><div class="card"><div class="card-header"><span>All doctors</span></div><div class="card-body">
    @if($summary['count'])@include('partials.rating-summary', ['summary' => $summary])@else<div class="rv-empty"><i class="bi bi-star"></i>No ratings yet.</div>@endif
    @if($hidden)<div class="small text-secondary mt-3"><i class="bi bi-eye-slash"></i> {{ $hidden }} hidden {{ \Illuminate\Support\Str::plural('review', $hidden) }} (not counted)</div>@endif</div></div></div>
  <div class="col-lg-8"><div class="card"><div class="card-header"><span>Reviews ({{ $reviews->total() }})</span></div><div class="card-body pt-1">
    @forelse($reviews as $rv)
      <div class="d-flex gap-2 align-items-start">
        <div class="flex-grow-1">@include('partials.review-item', ['rv' => $rv, 'full' => true, 'showDoctor' => true])</div>
        <div class="d-flex gap-1 pt-3">
          <form method="post" action="{{ route('admin.reviews.toggle',$rv) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-light border" title="{{ $rv->is_visible ? 'Hide from website' : 'Show on website' }}"><i class="bi bi-eye{{ $rv->is_visible ? '-slash' : '' }}"></i></button></form>
          <form method="post" action="{{ route('admin.reviews.destroy',$rv) }}" onsubmit="return confirm('Delete this review permanently?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button></form>
        </div>
      </div>
    @empty<div class="rv-empty"><i class="bi bi-chat-heart"></i>No reviews to show.</div>@endforelse
    <div class="mt-3">{{ $reviews->links('pagination::bootstrap-5') }}</div></div></div></div>
</div>
@endsection
