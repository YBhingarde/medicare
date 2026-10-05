<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $tx->reference }}</title>
    <style>
        body {
            font-family: Inter, system-ui, Arial, sans-serif;
            background: #f4f7fc;
            color: #1f2333;
            margin: 0;
            padding: 24px
        }

        .r {
            max-width: 640px;
            margin: auto;
            background: #fff;
            border: 1px solid #e8eaf0;
            border-radius: 12px;
            padding: 32px
        }

        h1 {
            font-size: 20px;
            margin: 0;
            color: #2563eb
        }

        .muted {
            color: #7b8194;
            font-size: 13px
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px
        }

        td {
            padding: 9px 0;
            border-bottom: 1px solid #eef0f5;
            font-size: 14px
        }

        td:last-child {
            text-align: right;
            font-weight: 600
        }

        .tot td {
            border-bottom: 0;
            font-size: 17px;
            padding-top: 14px
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px
        }

        .pill {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
            background: #e3f6ec;
            color: #177a43
        }

        .pill.refunded {
            background: #e4f0fe;
            color: #1f6fd6
        }

        button {
            margin-top: 22px;
            padding: 9px 18px;
            border: 0;
            border-radius: 8px;
            background: #2563eb;
            color: #fff;
            font-size: 14px;
            cursor: pointer
        }

        @media print {
            body {
                background: #fff;
                padding: 0
            }

            .r {
                border: 0
            }

            button {
                display: none
            }
        }
    </style>
</head>

<body>
    <div class="r">
        <div class="top">
            <div>
                <h1>MediCare</h1>
                <div class="muted">Payment receipt</div>
            </div>
            <div style="text-align:right"><b>{{ $tx->reference }}</b>
                <div class="muted">{{ ($tx->paid_at ?? $tx->created_at)->format('d M Y, g:i A') }}</div><span
                    class="pill {{ $tx->status }}">{{ ucfirst($tx->status) }}</span>
            </div>
        </div>
        <table>
            <tr>
                <td class="muted">Patient</td>
                <td>{{ $tx->patient->name }} (#{{ $tx->patient->patient_code }})</td>
            </tr>
            <tr>
                <td class="muted">Doctor</td>
                <td>{{ $tx->doctor->user->name }}<br><span class="muted">{{ $tx->doctor->speciality->name }}</span>
                </td>
            </tr>
            @if ($tx->doctor->hospital_name || $tx->doctor->hospital)
                <tr>
                    <td class="muted">Hospital</td>
                    <td>{{ $tx->doctor->hospital?->name ?? $tx->doctor->hospital_name }}</td>
                </tr>
            @endif
            <tr>
                <td class="muted">Appointment</td>
                <td>{{ $tx->appointment->date->format('d M Y') }},
                    {{ \Carbon\Carbon::parse($tx->appointment->time)->format('g:i A') }}</td>
            </tr>
            <tr>
                <td class="muted">Mode</td>
                <td>{{ $tx->appointment->mode ?? 'In-person' }}</td>
            </tr>
            <tr>
                <td class="muted">Payment method</td>
                <td>{{ $tx->method }}</td>
            </tr>
            <tr class="tot">
                <td>{{ $tx->status === 'refunded' ? 'Amount refunded' : 'Amount paid' }}</td>
                <td>&#8377;{{ number_format($tx->amount) }}</td>
            </tr>
        </table>
        <button onclick="window.print()">Print / Save as PDF</button>
    </div>
</body>

</html>
