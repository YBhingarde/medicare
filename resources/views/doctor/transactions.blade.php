@extends('layouts.admin')
@section('title', 'Transactions')
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="page-title">Transactions <span class="tag ms-1">{{ $counts['all'] }}</span></h1>
    </div>

    @include('partials.tx-totals', ['route' => 'doctor.transactions', 'totals' => $totals, 'labels' => ['Earned', 'Pending', 'Refunded']])
    @include('partials.tx-filter', ['route' => 'doctor.transactions', 'placeholder' => 'Search patient or reference'])

    <div class="card fx"><div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Reference</th><th>Date</th><th>Patient</th><th>Mode</th><th>Amount</th><th>Method</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($transactions as $t)
                <tr>
                    <td class="fw-semibold">{{ $t->reference }}</td>
                    <td class="text-nowrap">{{ ($t->paid_at ?? $t->created_at)->format('d M Y') }}<div class="small text-secondary">Visit {{ $t->appointment->date->format('d M') }}</div></td>
                    <td><div class="d-flex align-items-center gap-2"><span class="avatar-sm">{{ $t->patient->initials }}</span><div>{{ $t->patient->name }}<div class="small text-secondary">{{ $t->patient->phone ?: $t->patient->email }}</div></div></div></td>
                    <td>{{ $t->appointment->mode ?? 'In-person' }}</td>
                    <td class="fw-semibold">&#8377;{{ number_format($t->amount) }}</td>
                    <td class="small">{{ $t->method }}</td>
                    <td><span class="status {{ \App\Models\Transaction::PILL[$t->status] }}">{{ ucfirst($t->status) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">{{ request()->hasAny(['q', 'status', 'from', 'to']) ? 'No transactions match these filters.' : 'No transactions yet. They appear when patients book with you.' }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
    <div class="mt-3">{{ $transactions->links('pagination::bootstrap-5') }}</div>
@endsection
