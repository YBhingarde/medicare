@extends('layouts.app')
@section('title', 'Find doctors')
@section('full', '1')
@section('content')
    @php
        $keep = request()->except('speciality', 'page');
        $mf = (int) request('max_fees', 5000);
        $mf = $mf > 0 ? min($mf, 5000) : 5000;
        $sort = request('sort', 'fees_asc');
        $sortLabels = ['fees_asc' => 'Lowest fees', 'fees_desc' => 'Highest fees', 'exp' => 'Most experienced', 'rating' => 'Top rated'];
        $hasFilter = request()->hasAny(['q', 'speciality', 'city', 'max_fees', 'today', 'online']);
    @endphp
    <div class="mc-wrap mc-fd-page">

        {{-- ============ HERO ============ --}}
        <section class="mc-fdh mc-up">
            <span class="mc-fdh-blob b1"></span><span class="mc-fdh-blob b2"></span>
            <div class="mc-fdh-copy">
                <span class="mc-fdh-eyebrow"><i class="bi bi-patch-check-fill"></i> Verified doctors</span>
                <h1>Find the right <span>doctor</span></h1>
                <p>Filter by speciality, city or fees and book instantly</p>
            </div>
            <div class="mc-fdh-art" aria-hidden="true">
                <img src="{{ asset('img/hero-doctor.webp') }}" alt="" width="900" height="844">
                <div class="mc-fdh-badge">Your Health<br><b>Our Priority</b> <i class="bi bi-activity"></i></div>
                <ul class="mc-fdh-list">
                    <li><i class="bi bi-check-square-fill"></i> Instant Booking</li>
                    <li><i class="bi bi-check-square-fill"></i> Verified Doctors</li>
                    <li><i class="bi bi-check-square-fill"></i> Secure &amp; Private</li>
                </ul>
            </div>
            <nav class="mc-fdh-chips" aria-label="Specialities">
                <a href="{{ route('doctors', $keep) }}" class="{{ request('speciality') ? '' : 'on' }}"><span class="ci c0"><i class="bi bi-grid-fill"></i></span>All</a>
                @foreach ($specialities as $s)
                    <a href="{{ route('doctors', $keep + ['speciality' => $s->id]) }}" class="{{ request('speciality') == $s->id ? 'on' : '' }}"><span class="ci c{{ ($loop->index % 6) + 1 }}"><i class="bi bi-{{ $s->icon }}"></i></span>{{ $s->name }}</a>
                @endforeach
            </nav>
        </section>

        <div class="mc-fd-layout">
            {{-- ============ FILTERS ============ --}}
            <aside class="mc-fd-side mc-up" style="--d:80ms">
                <form class="mc-fdf" id="filterForm" method="get" action="{{ route('doctors') }}">
                    <div class="mc-fdf-h">
                        <span><i class="bi bi-funnel"></i> Filters</span>
                        @if ($hasFilter)<a href="{{ route('doctors') }}">Clear all</a>@endif
                    </div>
                    <label class="mc-fdf-l" for="fq">Doctor name</label>
                    <div class="mc-fdf-search"><input id="fq" name="q" value="{{ request('q') }}" placeholder="e.g. Sneha"><i class="bi bi-search"></i></div>

                    <label class="mc-fdf-l" for="fs">Speciality</label>
                    <select id="fs" name="speciality" class="mc-fdf-sel">
                        <option value="">Any</option>
                        @foreach ($specialities as $s)
                            <option value="{{ $s->id }}" @selected(request('speciality') == $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>

                    <label class="mc-fdf-l" for="fc">City</label>
                    <select id="fc" name="city" class="mc-fdf-sel">
                        <option value="">Any</option>
                        @foreach ($cities as $c)
                            <option value="{{ $c->id }}" @selected(request('city') == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>

                    <label class="mc-fdf-l" for="feeRange">Maximum fees (&#8377;)</label>
                    <input type="range" class="mc-range" id="feeRange" name="max_fees" min="100" max="5000" step="100" value="{{ $mf }}">
                    <div class="mc-range-lab"><span>&#8377;100</span><b id="feeOut">{{ $mf >= 5000 ? 'Any fees' : 'Up to &#8377;' . number_format($mf) }}</b><span>&#8377;5,000+</span></div>

                    <div class="mc-fdf-l mt-3">Availability</div>
                    <label class="mc-chk"><input type="checkbox" name="today" value="1" @checked(request()->boolean('today'))><span></span> Available today</label>
                    <label class="mc-chk"><input type="checkbox" name="online" value="1" @checked(request()->boolean('online'))><span></span> Online consultation</label>

                    <button class="btn btn-primary mc-btn mc-fdf-go"><i class="bi bi-funnel"></i> Apply filters</button>
                </form>
            </aside>

            {{-- ============ RESULTS ============ --}}
            <section class="mc-fd-res">
                <div class="mc-fd-bar mc-up" style="--d:120ms">
                    <h2>{{ $doctors->total() }} {{ Str::plural('doctor', $doctors->total()) }} found</h2>
                    <div class="mc-fd-tools">
                        <label class="mc-sort">Sorted by
                            <select name="sort" form="filterForm" data-autosubmit>
                                @foreach ($sortLabels as $k => $l)
                                    <option value="{{ $k }}" @selected($sort === $k)>{{ strtolower($l) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <div class="mc-vt" role="group" aria-label="Layout">
                            <button type="button" data-view="grid" class="on" aria-label="Grid view"><i class="bi bi-grid-fill"></i></button>
                            <button type="button" data-view="list" aria-label="List view"><i class="bi bi-list-ul"></i></button>
                        </div>
                    </div>
                </div>

                <div class="mc-dr-grid" id="docGrid">
                    @forelse($doctors as $d)
                        <div class="mc-dr-wrap mc-up" style="--d:{{ $loop->index * 60 }}ms">@include('partials.doctor-card', ['d' => $d])</div>
                    @empty
                        <div class="mc-empty" style="grid-column:1/-1">
                            <i class="bi bi-search"></i>
                            <h3 class="h6 mt-2">No doctor matches these filters</h3>
                            <p class="text-secondary mb-3">Try removing one filter.</p>
                            <a href="{{ route('doctors') }}" class="btn btn-primary btn-sm mc-btn">Clear filters</a>
                        </div>
                    @endforelse
                </div>
                <div class="mt-4">{{ $doctors->links('pagination::bootstrap-5') }}</div>
            </section>
        </div>
    </div>
@endsection