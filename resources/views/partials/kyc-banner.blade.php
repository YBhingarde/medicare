{{-- Shown on the doctor dashboard until the admin has approved the doctor. Expects $doctor. --}}
@php
  $st = $doctor->verification_status;
  $b = [
    'incomplete' => ['#fff6e0', '#f0d9a0', '#b56f00', 'exclamation-triangle-fill', 'Complete your eKYC to go live', 'Upload your profile photo and verification documents. Until our team approves them, patients cannot see or book you.', 'Start eKYC'],
    'pending' => ['#eef0ff', '#d5d8fb', '#2563eb', 'hourglass-split', 'eKYC under review', 'Our admin team is checking your documents. Your profile goes live on the website as soon as you are approved.', 'View status'],
    'rejected' => ['#fdeaea', '#f3c2c4', '#c62d33', 'x-circle-fill', 'eKYC rejected', 'Reason: ' . $doctor->rejection_reason . ' Please fix it and submit again.', 'Fix and resubmit'],
  ][$st] ?? null;
@endphp
@if($b)
<div class="d-flex flex-wrap align-items-center gap-3 rounded-4 p-3 mb-3" style="background:{{ $b[0] }};border:1px solid {{ $b[1] }}">
  <div style="width:44px;height:44px;border-radius:12px;background:#fff;color:{{ $b[2] }};display:grid;place-items:center;font-size:1.25rem;flex:none"><i class="bi bi-{{ $b[3] }}"></i></div>
  <div class="flex-grow-1"><b>{{ $b[4] }}</b><div class="small text-secondary">{{ $b[5] }}</div></div>
  <a href="{{ route('doctor.kyc') }}" class="btn btn-primary btn-sm">{{ $b[6] }}</a>
</div>
@endif
