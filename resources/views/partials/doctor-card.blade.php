{{-- Find-doctors card. expects $d (Doctor) with ->next_slot set by the controller --}}
@php
    $sl = $d->next_slot ?? null;
    $av = $d->availability ?? [];
    $days = ['mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat', 'sun' => 'Sun'];
    $nextKey = $sl ? strtolower(\Carbon\Carbon::parse($sl['date'])->format('D')) : null;
    $dayLabel = $sl ? ($sl['today'] ? 'Today' : ($sl['tomorrow'] ? 'Tomorrow' : \Carbon\Carbon::parse($sl['date'])->format('D, j M'))) : null;
@endphp
<article class="mc-dr">
    <a class="mc-dr-ph" href="{{ route('doctors.show', $d) }}" tabindex="-1" aria-hidden="true">
        @if ($d->photo_url)
            <span class="ph-bg" style="background-image:url('{{ $d->photo_url }}')"></span>
            <img src="{{ $d->photo_url }}" alt="{{ $d->user->name }}" loading="lazy">
        @else
            <div class="mc-ph-init mc-dr-init">{{ $d->user->initials }}</div>
        @endif
        @if ($sl && $sl['today'])
            <span class="mc-avail"><i></i> Available Today</span>
        @elseif ($sl)
            <span class="mc-avail soon"><i></i> {{ $sl['tomorrow'] ? 'Tomorrow' : \Carbon\Carbon::parse($sl['date'])->format('D, j M') }}</span>
        @else
            <span class="mc-avail off"><i></i> No slots soon</span>
        @endif
    </a>

    <div class="mc-dr-main">
        <h3><a href="{{ route('doctors.show', $d) }}">{{ $d->user->name }}</a></h3>
        <div class="sp"><i class="bi bi-{{ $d->speciality->icon }}"></i> {{ $d->speciality->name }}</div>
        <div class="rt">
            <i class="bi bi-star-fill"></i>
            @if (($d->reviews_count ?? 0) > 0)
                <b>{{ number_format($d->reviews_avg_rating, 1) }}</b> <small>({{ $d->reviews_count }} reviews)</small>
            @else
                <b>New</b>
            @endif
        </div>
        <div class="mt"><span><i class="bi bi-briefcase"></i> {{ $d->experience_years }} yrs exp</span><span><i class="bi bi-geo-alt"></i> {{ $d->city->name }}</span></div>
        <div class="dy" title="Days available">
            @foreach ($days as $k => $l)
                @php $open = empty($av) ? true : ! empty($av[$k] ?? []); @endphp
                <span class="{{ $open ? 'on' : '' }} {{ $k === $nextKey ? 'next' : '' }}">{{ $l }}</span>
            @endforeach
        </div>
    </div>

    <div class="mc-dr-side">
        <button type="button" class="mc-fav" data-fav="{{ $d->id }}" aria-label="Save {{ $d->user->name }}"><i class="bi bi-heart"></i></button>
        <div class="nx">
            <small>Next available</small>
            @if ($sl)
                <b><i class="bi bi-calendar2-check"></i> {{ $dayLabel }}</b>
                <span>{{ \Carbon\Carbon::parse($sl['date'] . ' ' . $sl['time'])->format('g:i A') }}</span>
            @else
                <b><i class="bi bi-calendar2-x"></i> See schedule</b>
            @endif
        </div>
        <div class="price">&#8377;{{ number_format($d->fees) }} <small>/ visit</small></div>
        <a href="{{ $sl ? route('doctors.show', ['doctor' => $d, 'date' => $sl['date']]) : route('doctors.show', $d) }}" class="btn btn-primary mc-btn mc-dr-book">Book slot <i class="bi bi-arrow-right"></i></a>
    </div>
</article>