{{-- expects $v (0-5, decimals ok). Optional $cls --}}
@php $v = (float) ($v ?? 0); @endphp
<span class="stars {{ $cls ?? '' }}" role="img" aria-label="{{ number_format($v, 1) }} out of 5">
    @for ($i = 1; $i <= 5; $i++)
        <i class="bi bi-star{{ $v >= $i - 0.25 ? '-fill' : ($v >= $i - 0.75 ? '-half' : '') }}"></i>
    @endfor
</span>
