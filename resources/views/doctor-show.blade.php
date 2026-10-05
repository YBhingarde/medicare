@extends('layouts.app')
@section('title', $doctor->user->name)
@section('content')
    @php
        $D = \App\Models\Doctor::DAYS;
        $av = $doctor->availability ?? [];
        $firstDay = collect(array_keys($D))->first(fn($k) => !empty($av[$k] ?? [])) ?? 'mon';
    @endphp
    <a href="{{ route('doctors') }}" class="text-decoration-none small text-secondary mc-up">
        <i class="bi bi-arrow-left"></i> All doctors</a>

    <div class="mc-panel mt-2 mb-4 mc-up">
        <div class="d-flex flex-wrap justify-content-between gap-3 align-items-center">
            <div class="mc-prof">
                <div class="pic">@include('partials.doc-photo', ['d' => $doctor, 'cls' => 'sm'])</div>
                <div>
                    <h1 class="h3 fw-bold mb-1">{{ $doctor->user->name }} <i class="bi bi-patch-check-fill text-primary fs-6"
                            title="Verified"></i></h1>
                    <div class="text-secondary mb-2">{{ $doctor->designation ?: $doctor->speciality->name }} &middot;
                        {{ $doctor->qualification }}</div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="mc-pill"><i class="bi bi-heart-pulse"></i> {{ $doctor->speciality->name }}</span>
                        <span class="mc-pill"><i class="bi bi-briefcase"></i> {{ $doctor->experience_years }} yrs
                            experience</span>
                        <span class="mc-pill"><i class="bi bi-geo-alt"></i> {{ $doctor->city->name }}</span>
                        <span class="mc-pill ok"><i class="bi bi-check-circle"></i> Available</span>
                        @if ($summary['count'])
                            <a href="#reviews" class="mc-pill text-decoration-none"><i class="bi bi-star-fill"
                                    style="color:#f5a623"></i> <b>{{ number_format($summary['avg'], 1) }}</b>
                                ({{ $summary['count'] }}
                                {{ \Illuminate\Support\Str::plural('review', $summary['count']) }})</a>
                        @endif
                    </div>
                </div>
            </div>
            <div class="text-lg-end">
                <div class="mc-sub">Consultation charge</div>
                <div class="fs-3 fw-bold text-primary">&#8377;{{ number_format($doctor->fees) }} <small
                        class="fs-6 text-secondary fw-normal">/ {{ $doctor->slot_minutes }} min</small></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            @if ($av)
                <div class="mc-panel mb-4 mc-reveal">
                    <h2><i class="bi bi-calendar-week"></i> Availability</h2>
                    <div class="mc-tabs">
                        @foreach ($D as $k => $name)
                            <button type="button" class="mc-tab {{ $k === $firstDay ? 'active' : '' }}"
                                data-tab="{{ $k }}">{{ $name }}</button>
                        @endforeach
                    </div>
                    @foreach ($D as $k => $name)
                        <div data-pane="{{ $k }}" {{ $k === $firstDay ? '' : 'hidden' }}>
                            @forelse($av[$k] ?? [] as [$f,$t])
                                <span class="mc-win"><i class="bi bi-clock text-primary"></i>
                                    {{ \Carbon\Carbon::parse($f)->format('g:i A') }} -
                                    {{ \Carbon\Carbon::parse($t)->format('g:i A') }}</span>
                            @empty <span class="text-secondary small"><i class="bi bi-moon-stars"></i> Not available on
                                    {{ $name }}</span>
                            @endforelse
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="mc-panel mb-4 mc-reveal">
                <h2><i class="bi bi-person-lines-fill"></i> Short bio</h2>
                <p class="text-secondary mb-2 {{ strlen($doctor->about ?? '') > 220 ? 'mc-clamp' : '' }}" id="bioText">
                    {{ $doctor->about ?: 'No details added yet.' }}</p>
                @if (strlen($doctor->about ?? '') > 220)
                    <button type="button" id="bioMore" class="btn btn-link p-0 text-decoration-none small">See more <i
                            class="bi bi-chevron-down"></i></button>
                @endif
            </div>

            @if (!empty($doctor->education))
                <div class="mc-panel mb-4 mc-reveal">
                    <h2><i class="bi bi-mortarboard"></i> Education</h2>
                    @foreach ($doctor->education as $e)
                        <div class="mc-line">
                            <div class="fw-semibold">{{ $e['school'] }}@if (!empty($e['degree']))
                                    - {{ $e['degree'] }}
                                @endif
                            </div>
                            @if (!empty($e['from']) || !empty($e['to']))
                                <div class="small text-secondary">{{ $e['from'] ?? '' }} - {{ $e['to'] ?? '' }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            @if (!empty($doctor->awards))
                <div class="mc-panel mb-4 mc-reveal">
                    <h2><i class="bi bi-trophy"></i> Awards &amp; recognition</h2>
                    @foreach ($doctor->awards as $a)
                        <div class="mc-item">
                            <div class="ic"><i class="bi bi-award"></i></div>
                            <div>
                                <div class="fw-semibold">{{ $a['title'] }}</div>
                                <div class="small text-secondary">{{ $a['description'] ?? '' }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if (!empty($doctor->certifications))
                <div class="mc-panel mb-4 mc-reveal">
                    <h2><i class="bi bi-patch-check"></i> Certifications</h2>
                    @foreach ($doctor->certifications as $c)
                        <div class="mc-item">
                            <div class="ic"><i class="bi bi-file-earmark-check"></i></div>
                            <div>
                                <div class="fw-semibold">{{ $c['title'] }}</div>
                                <div class="small text-secondary">{{ $c['description'] ?? '' }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="mc-panel mb-4 mc-reveal" id="reviews">
                <h2><i class="bi bi-star"></i> Patient reviews</h2>
                @if ($summary['count'])
                    @include('partials.rating-summary', ['summary' => $summary])
                    <hr class="my-3">
                    @foreach ($reviews as $rv)
                        @include('partials.review-item', ['rv' => $rv])
                    @endforeach
                @else
                    <div class="rv-empty"><i class="bi bi-star"></i>No reviews yet. Patients can rate this doctor after a
                        completed visit.</div>
                @endif
            </div>
        </div>

        <div class="col-lg-4">
            <div class="mc-panel mc-book mb-4 mc-up" style="--d:120ms">
                <h2><i class="bi bi-calendar2-plus"></i> Book an appointment</h2>
                <form method="get" class="mb-3"><label class="form-label small text-secondary fw-semibold">Choose
                        date</label>
                    <input type="date" name="date" value="{{ $date }}" min="{{ now()->toDateString() }}"
                        max="{{ $maxDate }}" class="form-control" onchange="this.form.submit()">
                </form>
                @error('time')
                    <div class="alert alert-danger py-2 small">{{ $message }}</div>
                @enderror
                @if (!$board)
                    <div class="text-center py-3"><i class="bi bi-calendar-x fs-3 text-secondary"></i>
                        <p class="text-secondary small mt-2 mb-0">{{ $onLeave ? 'The doctor is on leave on this date. Try another day.' : 'No slots on this date. Try another day.' }}</p>
                    </div>
                @else
                    @if (!$slots)
                        <div class="alert alert-warning py-2 small"><i class="bi bi-people-fill"></i> All slots are full on this date. Try another day.</div>
                    @endif
                    @auth
                        @if (auth()->user()->role === 'patient')
                            <form method="post" action="{{ route('book', $doctor) }}" data-loading>@csrf
                                <input type="hidden" name="date" value="{{ $date }}">
                                <div class="mc-slots mb-3">
                                    @foreach ($board as $s)
                                        <input type="radio" class="btn-check" name="time" id="t{{ $loop->index }}"
                                            value="{{ $s['time'] }}" @disabled($s['full']) required>
                                        <label for="t{{ $loop->index }}" class="{{ $s['full'] ? 'is-full' : '' }}"
                                            style="--d:{{ min($loop->index, 14) * 30 }}ms">{{ \Carbon\Carbon::parse($s['time'])->format('g:i A') }}
                                            @if ($s['full'])<small>Full</small>
                                            @elseif ($doctor->capacity() > 1)<small>{{ $s['left'] }} left</small>@endif
                                        </label>
                                    @endforeach
                                </div>
                                @if ($doctor->appointment_type === 'Both')
                                    <div class="mb-3"><label class="form-label small text-secondary fw-semibold">Visit
                                            type</label>
                                        <select name="mode" class="form-select">
                                            <option value="In-person">In-person (clinic)</option>
                                            <option value="Online">Online (video)</option>
                                        </select>
                                    </div>
                                @endif
                                @if (\App\Support\Razorpay::isReady() && (int) $doctor->fees > 0)
                                    <div class="mb-3"><label class="form-label small text-secondary fw-semibold">Pay fee (&#8377;{{ number_format($doctor->fees) }})</label>
                                        <div class="form-check"><input class="form-check-input" type="radio" name="payment" id="payOnline" value="online" checked>
                                            <label class="form-check-label" for="payOnline">Pay online now <span class="text-secondary small">(UPI, card, net banking)</span></label></div>
                                        <div class="form-check"><input class="form-check-input" type="radio" name="payment" id="payClinic" value="clinic">
                                            <label class="form-check-label" for="payClinic">Pay at the clinic <span class="text-secondary small">(in-person visits only)</span></label></div>
                                        <div class="small text-secondary mt-1"><i class="bi bi-shield-check"></i> Video visits are always paid online. Cancelled visits are refunded automatically.</div>
                                    </div>
                                @endif
                                <div class="mb-3"><label class="form-label small text-secondary fw-semibold">Reason for
                                        visit (optional)</label><input name="reason" class="form-control" maxlength="255" value="{{ old('reason', $rebook ? 'Follow-up visit' : '') }}">
                                </div>
                                <button type="submit" class="btn btn-primary w-100 mc-btn py-2">Request appointment</button>
                            </form>
                        @else
                            <p class="text-secondary small">Only patient accounts can book appointments.</p>
                        @endif
                    @else
                        <p class="text-secondary small"><span class="mc-pill ok">{{ count($slots) }} slots with room</span> on
                            this date.</p>
                        <a class="btn btn-primary w-100 mc-btn py-2" href="{{ route('login', ['redirect' => request()->getRequestUri()]) }}">Log in to book</a>
                    @endauth
                @endif
                <div class="small text-secondary mt-3"><i class="bi bi-info-circle"></i>
                    {{ $doctor->appointment_type ?: 'Clinic' }} visit &middot; bookings open
                    {{ $doctor->advance_days ?: 30 }} days ahead</div>
            </div>

            <div class="mc-panel mc-about mc-reveal">
                <h2><i class="bi bi-info-circle"></i> About</h2>
                <dl class="mb-0">
                    @if ($doctor->license_no)
                        <dt>Medical licence number</dt>
                        <dd>{{ $doctor->license_no }}</dd>
                    @endif
                    @if ($doctor->hospital || $doctor->hospital_name || $doctor->address)
                        <dt>Location</dt>
                        <dd>{{ $doctor->hospital?->name ?? $doctor->hospital_name }}
                            {{ $doctor->address ?: $doctor->hospital_address }}{{ $doctor->hospital_area ? ', ' . $doctor->hospital_area : '' }}, {{ $doctor->city->name }}{{ $doctor->hospital_pincode ? ' - ' . $doctor->hospital_pincode : '' }}
                            @if ($doctor->hospital_map_url)<a href="{{ $doctor->hospital_map_url }}" target="_blank" rel="noopener" class="ms-1 text-decoration-none"><i class="bi bi-geo-alt-fill"></i> View on map</a>@endif</dd>
                    @endif
                    <dt>Experience</dt>
                    <dd>{{ $doctor->experience_years }}+ years</dd>
                    @if ($doctor->gender)
                        <dt>Gender</dt>
                        <dd>{{ $doctor->gender }}</dd>
                    @endif
                    @if (!empty($doctor->languages))
                        <dt>Languages</dt>
                        <dd>
                            @foreach ($doctor->languages as $l)
                                <span class="mc-pill me-1 mb-1">{{ $l }}</span>
                            @endforeach
                        </dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>
@endsection
