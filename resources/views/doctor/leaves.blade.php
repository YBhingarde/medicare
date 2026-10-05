@extends('layouts.admin')
@section('title', 'My Leaves')
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="page-title">My Leaves</h1>
    </div>
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card fx p-3">
                <h5 class="mb-1">Block leave days</h5>
                <p class="small text-secondary">Patients cannot book you on these days. For a single day, leave "To" empty.</p>
                <form method="post" action="{{ route('doctor.leaves.store') }}">@csrf
                    <div class="row g-2 mb-2">
                        <div class="col-6"><label class="form-label small fw-semibold">From</label>
                            <input type="date" name="from" value="{{ old('from') }}" min="{{ now()->toDateString() }}" class="form-control @error('from') is-invalid @enderror" required></div>
                        <div class="col-6"><label class="form-label small fw-semibold">To (optional)</label>
                            <input type="date" name="to" value="{{ old('to') }}" min="{{ now()->toDateString() }}" class="form-control @error('to') is-invalid @enderror"></div>
                    </div>
                    <div class="mb-2"><label class="form-label small fw-semibold">Reason (optional)</label>
                        <input name="reason" value="{{ old('reason') }}" maxlength="150" class="form-control" placeholder="Conference, personal leave..."></div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="cancel_existing" value="1" id="cx" @checked(old('cancel_existing'))>
                        <label class="form-check-label small" for="cx">Cancel those appointments and notify the patients</label>
                    </div>
                    <button class="btn btn-primary"><i class="bi bi-calendar-x"></i> Save leave</button>
                </form>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card fx">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Date</th><th>Day</th><th>Reason</th><th class="text-end"></th></tr></thead>
                        <tbody>
                            @forelse($leaves as $l)
                                <tr>
                                    <td>{{ $l->date->format('d M Y') }}</td>
                                    <td>{{ $l->date->format('l') }}</td>
                                    <td class="small text-secondary">{{ $l->reason ?: '-' }}</td>
                                    <td class="text-end">
                                        <form method="post" action="{{ route('doctor.leaves.destroy', $l) }}" class="d-inline" onsubmit="return confirm('Remove this leave day?')">@csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="empty">No upcoming leave days.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($past->isNotEmpty())
                <p class="small text-secondary mt-3 mb-1">Recent past leaves</p>
                <div class="small text-secondary">{{ $past->map(fn ($l) => $l->date->format('d M Y'))->implode(' · ') }}</div>
            @endif
        </div>
    </div>
@endsection
