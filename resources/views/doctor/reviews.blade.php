@extends('layouts.admin')
@section('title','My reviews')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><h1 class="page-title">Patient Reviews</h1>
  <form method="get" class="d-flex gap-2"><select name="stars" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Filter by stars">
    <option value="">All ratings</option>@foreach([5,4,3,2,1] as $s)<option value="{{ $s }}" @selected(request('stars')==$s)>{{ $s }} star{{ $s>1?'s':'' }}</option>@endforeach</select>
    @if(request('stars'))<a class="btn btn-light btn-sm" href="{{ route('doctor.reviews') }}">Clear</a>@endif</form></div>
<div class="row g-3">
  <div class="col-lg-4"><div class="card"><div class="card-header"><span>Overall rating</span></div><div class="card-body">
    @if($summary['count'])@include('partials.rating-summary', ['summary' => $summary])@else<div class="rv-empty"><i class="bi bi-star"></i>No ratings yet.</div>@endif</div></div></div>
  <div class="col-lg-8"><div class="card"><div class="card-header"><span>Feedback ({{ $reviews->total() }})</span></div><div class="card-body pt-1">
    @forelse($reviews as $rv)@include('partials.review-item', ['rv' => $rv, 'full' => true])
    @empty<div class="rv-empty"><i class="bi bi-chat-heart"></i>No reviews to show.</div>@endforelse
    <div class="mt-3">{{ $reviews->links('pagination::bootstrap-5') }}</div></div></div></div>
</div>
@endsection
