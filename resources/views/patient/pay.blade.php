@extends('layouts.admin')
@section('title', 'Pay for your appointment')
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="page-title">Complete your payment</h1>
        <a class="btn btn-light btn-sm" href="{{ route('patient.appointments') }}"><i class="bi bi-arrow-left"></i> My appointments</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card fx p-4">
                <div class="d-flex align-items-center gap-2 mb-3">@include('partials.doc-photo-sm', ['d' => $a->doctor])
                    <div><b>{{ $a->doctor->user->name }}</b><div class="small text-secondary">{{ $a->doctor->speciality->name }}</div></div>
                </div>
                <table class="table table-sm mb-3">
                    <tr><td class="text-secondary">Date &amp; time</td><td class="text-end fw-semibold">{{ $a->date->format('d M Y') }}, {{ $a->startsAt()->format('g:i A') }}</td></tr>
                    <tr><td class="text-secondary">Visit type</td><td class="text-end">{{ $a->mode }}</td></tr>
                    <tr><td class="text-secondary">Reference</td><td class="text-end">{{ $tx->reference }}</td></tr>
                    <tr><td class="fw-semibold">Amount to pay</td><td class="text-end fs-5 fw-bold">&#8377;{{ number_format($tx->amount) }}</td></tr>
                </table>

                <button type="button" id="payBtn" class="btn btn-success w-100 py-2 mc-btn"><i class="bi bi-lock-fill"></i> Pay &#8377;{{ number_format($tx->amount) }} securely</button>
                <p class="small text-secondary text-center mt-3 mb-0"><i class="bi bi-clock"></i> Please pay within <b>{{ $minutes }} minutes</b>, otherwise the slot is released.
                    Payments are processed by Razorpay; MediCare never sees your card or UPI details.</p>

                <form id="payForm" method="post" action="{{ route('patient.pay.verify', $a) }}">@csrf
                    <input type="hidden" name="razorpay_payment_id" id="rzp_pid">
                    <input type="hidden" name="razorpay_order_id" id="rzp_oid">
                    <input type="hidden" name="razorpay_signature" id="rzp_sig">
                </form>
            </div>
        </div>
    </div>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        (function () {
            var btn = document.getElementById('payBtn');
            var opts = {
                key: @json($keyId),
                order_id: @json($tx->gateway_order_id),
                amount: {{ (int) $tx->amount * 100 }},
                currency: 'INR',
                name: 'MediCare',
                description: @json('Consultation with ' . $a->doctor->user->name),
                prefill: { name: @json($user->name), email: @json($user->email), contact: @json((string) $user->phone) },
                theme: { color: '#2563eb' },
                handler: function (res) {
                    btn.disabled = true;
                    btn.innerHTML = 'Confirming payment...';
                    document.getElementById('rzp_pid').value = res.razorpay_payment_id;
                    document.getElementById('rzp_oid').value = res.razorpay_order_id;
                    document.getElementById('rzp_sig').value = res.razorpay_signature;
                    document.getElementById('payForm').submit();
                },
                modal: { ondismiss: function () { btn.disabled = false; } }
            };
            function open() {
                if (typeof Razorpay === 'undefined') { alert('Payment window could not load. Check your internet connection and try again.'); return; }
                btn.disabled = true;
                var rz = new Razorpay(opts);
                rz.on('payment.failed', function (r) { btn.disabled = false; });
                rz.open();
            }
            btn.addEventListener('click', open);
            open(); // open the payment window straight away
        })();
    </script>
@endsection
