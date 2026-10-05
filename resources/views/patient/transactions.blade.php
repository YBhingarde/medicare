@extends('layouts.admin')
@section('title', 'Transactions')
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="page-title">Transactions <span class="tag ms-1">{{ $counts['all'] }}</span></h1>
        <a class="btn btn-primary btn-sm" href="{{ route('doctors') }}"><i class="bi bi-plus-lg"></i> New Appointment</a>
    </div>

    @include('partials.tx-totals', ['route' => 'patient.transactions', 'totals' => $totals])
    @include('partials.tx-filter', ['route' => 'patient.transactions', 'placeholder' => 'Search doctor or reference'])

    <div class="card fx"><div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Reference</th><th>Date</th><th>Doctor Name</th><th>Amount</th><th>Method</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($transactions as $t)
                <tr>
                    <td class="fw-semibold">{{ $t->reference }}</td>
                    <td class="text-nowrap">{{ ($t->paid_at ?? $t->created_at)->format('d M Y') }}<div class="small text-secondary">Visit {{ $t->appointment->date->format('d M') }}</div></td>
                    <td><div class="d-flex align-items-center gap-2">@include('partials.doc-photo-sm', ['d' => $t->doctor])<div>{{ $t->doctor->user->name }}<div class="small text-secondary">{{ $t->doctor->speciality->name }}</div></div></div></td>
                    <td class="fw-semibold">&#8377;{{ number_format($t->amount) }}</td>
                    <td class="small">{{ $t->method }}</td>
                    <td><span class="status {{ \App\Models\Transaction::PILL[$t->status] }}">{{ ucfirst($t->status) }}</span></td>
                    <td class="text-end text-nowrap">
                        @if($t->awaitingPayment() && $t->appointment->isOpen())<a class="btn btn-sm btn-success" href="{{ route('patient.pay', $t->appointment) }}"><i class="bi bi-credit-card"></i> Pay now</a>@endif
                        @if(in_array($t->status, ['paid', 'refunded']))<a class="btn btn-sm btn-outline-primary" target="_blank" href="{{ route('patient.receipt', $t) }}"><i class="bi bi-receipt"></i> Receipt</a>@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">{{ request()->hasAny(['q', 'status', 'from', 'to']) ? 'No transactions match these filters.' : 'No transactions yet. They appear when you book an appointment.' }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
    <div class="mt-3">{{ $transactions->links('pagination::bootstrap-5') }}</div>
@endsection
