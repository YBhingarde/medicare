{{-- expects $d (Doctor). Shows M T W T F S S circles; hover shows that day's sessions. --}}
@php
    $av = $d->availability ?? [];
    $short = ['mon' => 'M', 'tue' => 'T', 'wed' => 'W', 'thu' => 'T', 'fri' => 'F', 'sat' => 'S', 'sun' => 'S'];
@endphp
<div class="day-dots">
    @foreach (\App\Models\Doctor::DAYS as $k => $name)
        @php
            $wins = empty($av) ? [[$d->slot_start, $d->slot_end]] : $av[$k] ?? [];
            $tip = $wins
                ? $name .
                    ': ' .
                    collect($wins)
                        ->map(
                            fn($w) => \Carbon\Carbon::parse($w[0])->format('g:i A') .
                                ' - ' .
                                \Carbon\Carbon::parse($w[1])->format('g:i A'),
                        )
                        ->implode(', ')
                : $name . ': Day off';
        @endphp
        <span class="dot {{ $wins ? 'on' : '' }}" title="{{ $tip }}">{{ $short[$k] }}</span>
    @endforeach
</div>
