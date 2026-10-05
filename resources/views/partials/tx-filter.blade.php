{{-- Filter bar for the Transactions pages. Params: $route (route name), $placeholder --}}
@php $filtered = request()->hasAny(['q', 'status', 'from', 'to']); @endphp
<form method="get" class="pp-filter mb-3">
  <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="{{ $placeholder }}" aria-label="Search">
  <select name="status" class="form-select form-select-sm" aria-label="Status">
    <option value="">All status</option>
    @foreach (['paid' => 'Paid', 'pending' => 'Pending', 'refunded' => 'Refunded', 'cancelled' => 'Cancelled'] as $k => $l)
      <option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>
    @endforeach
  </select>
  <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm" title="From date" aria-label="From date">
  <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm" title="To date" aria-label="To date">
  <button class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button>
  @if ($filtered)<a class="btn btn-light btn-sm" href="{{ route($route) }}">Clear</a>@endif
</form>
