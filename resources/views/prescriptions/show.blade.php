<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prescription {{ $a->rxNumber() }} - MediCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @include('prescriptions._style')
    <style>
        body {
            background: #eef1f8;
        }

        .paper {
            max-width: 794px;
            margin: 18px auto 40px;
            background: #fff;
            box-shadow: 0 6px 30px rgba(31, 35, 51, .14);
            border-radius: 6px;
            overflow: hidden;
        }

        @media print {
            body {
                background: #fff;
            }

            .toolbar {
                display: none !important;
            }

            .paper {
                box-shadow: none;
                margin: 0;
                max-width: none;
                border-radius: 0;
            }

            * {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>
    @php
        $role = auth()->user()->role;
        $back = match ($role) {
            'doctor' => route('doctor.appointments'),
            'admin' => route('admin.appointments'),
            default => route('patient.appointments'),
        };
    @endphp
    <div class="toolbar container py-3" style="max-width:794px">
        @if (session('ok'))
            <div class="alert alert-success py-2 mb-2">{{ session('ok') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-warning py-2 mb-2 small">{{ $errors->first() }}</div>
        @endif
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <a href="{{ $back }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
            <div class="ms-auto d-flex flex-wrap gap-2">
                @if ($role === 'doctor' && in_array($a->status, ['confirmed', 'in_progress', 'completed']))
                    <a href="{{ route('doctor.prescription.edit', $a) }}" class="btn btn-outline-primary btn-sm"><i
                            class="bi bi-pencil-square"></i> Edit</a>
                @endif
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i
                        class="bi bi-printer"></i> Print</button>
                <a href="{{ route('prescription.pdf', $a) }}" class="btn btn-primary btn-sm"><i
                        class="bi bi-file-earmark-pdf"></i> Download PDF</a>
            </div>
        </div>
    </div>
    <div class="paper">@include('prescriptions._body')</div>
</body>

</html>
