@extends('layouts.app')
@section('title', 'Find and book doctors')
@section('full', '1')
@section('content')
    {{-- ================= HERO (full width) ================= --}}
    <section class="mc-hero3" id="mcHero">
        <span class="mc-shape s1"></span><span class="mc-shape s2"></span><span class="mc-dots"></span>
        <div class="mc-wrap mc-hero3-in">
            <div class="mc-hero3-copy mc-up">
                <span class="mc-pill mb-3" style="background:rgba(255,255,255,.18);color:#fff"><i class="bi bi-stars"></i>
                    Trusted doctors, real availability</span>
                <h1>See the right doctor,<br><span class="mc-und">at a time that suits you</span></h1>
                <p class="lead">Search by name, speciality or city and book a slot in under a minute.</p>

                <form action="{{ route('doctors') }}" class="mc-sf">
                    <label class="mc-sf-f grow"><i class="bi bi-person"></i>
                        <input name="q" placeholder="Doctor name" aria-label="Doctor name"></label>
                    <label class="mc-sf-f"><i class="bi bi-grid"></i>
                        <select name="speciality" aria-label="Speciality">
                            <option value="">Any speciality</option>
                            @foreach ($specialities as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select></label>
                    <label class="mc-sf-f"><i class="bi bi-geo-alt"></i>
                        <select name="city" aria-label="City">
                            <option value="">Any city</option>
                            @foreach ($cities as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select></label>
                    <button class="btn btn-primary mc-btn mc-sf-go"><i class="bi bi-search"></i> Search</button>
                </form>

                <div class="mc-trust mt-3"><span><i class="bi bi-patch-check-fill"></i> Verified doctors</span><span><i
                            class="bi bi-lightning-charge-fill"></i> Instant booking</span><span><i
                            class="bi bi-shield-check"></i> Secure &amp; private</span></div>
            </div>

            {{-- doctor + floating cards: every .mc-layer follows the cursor (see site.js) --}}
            <div class="mc-hero3-art" aria-hidden="true">
                <div class="mc-layer l-glow" data-depth="10">
                    <div class="mc-glow"></div>
                </div>
                <div class="mc-layer l-doc" data-depth="22" data-tilt="1">
                    {{-- hover (mouse) or tap (touch) the doctor: she looks up at you (smooth cross-fade, see site.js) --}}
                    <div class="mc-doc-swap" data-doc-swap>
                        <img class="mc-doc-a" src="{{ asset('img/hero-doctor.webp') }}" alt="" width="900"
                            height="844" fetchpriority="high" draggable="false">
                        <img class="mc-doc-b" src="{{ asset('img/hero-doctor-look.webp') }}" alt="" width="900"
                            height="844" decoding="async" draggable="false">
                    </div>
                </div>
                <div class="mc-layer l-orb" data-depth="52">
                    <div class="mc-orb2"><i class="bi bi-heart-pulse-fill"></i></div>
                </div>
                @if (($ratingSummary['count'] ?? 0) > 0)
                    <div class="mc-layer l-f2" data-depth="46">
                        <div class="mc-fc"><i class="bi bi-star-fill" style="color:#f5a623;background:#fff4dc"></i>
                            <div><b>{{ number_format($ratingSummary['avg'], 1) }} / 5 rating</b><span>from
                                    {{ $ratingSummary['count'] }} patient
                                    review{{ $ratingSummary['count'] > 1 ? 's' : '' }}</span></div>
                        </div>
                    </div>
                @else
                    <div class="mc-layer l-f2" data-depth="46">
                        <div class="mc-fc"><i class="bi bi-clock-history"></i>
                            <div><b>Book in a minute</b><span>Pick a day &amp; time slot</span></div>
                        </div>
                    </div>
                @endif
                <div class="mc-layer l-f3" data-depth="60">
                    <div class="mc-fc" style="animation-delay:-3s"><i class="bi bi-patch-check-fill"
                            style="color:#17a673;background:#dcf5ea"></i>
                        <div><b>Verified doctors</b><span>Licence checked by admin</span></div>
                    </div>
                </div>
                <div class="mc-layer l-f1" data-depth="38">
                    <div class="mc-fc" style="animation-delay:-1.5s"><i class="bi bi-calendar2-check-fill"></i>
                        <div><b>Slot confirmed</b><span>Tomorrow, 10:30 AM</span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mc-wrap mc-stats3-wrap">
            <div class="mc-stats3">
                <div class="mc-st"><i class="bi bi-person-badge-fill"></i>
                    <div><b data-count="{{ $doctorCount }}">0</b><span>Verified doctors</span></div>
                </div>
                <div class="mc-st"><i class="bi bi-grid-fill"></i>
                    <div><b data-count="{{ count($specialities) }}">0</b><span>Specialities</span></div>
                </div>
                <div class="mc-st"><i class="bi bi-geo-alt-fill"></i>
                    <div><b data-count="{{ count($cities) }}">0</b><span>Cities</span></div>
                </div>
                <div class="mc-st"><i class="bi bi-calendar-check-fill"></i>
                    <div><b data-count="{{ $bookedCount }}">0</b><span>Appointments booked</span></div>
                </div>
            </div>
        </div>
    </section>

    <div class="mc-wrap mc-after-hero">
        {{-- ================= SPECIALITIES ================= --}}
        <section class="mc-sec">
            <div class="d-flex justify-content-between align-items-end mb-3 mc-reveal">
                <div>
                    <h2 class="mc-title">Browse by speciality</h2>
                    <div class="mc-sub">Pick a speciality to see who is available</div>
                </div>
                <a href="{{ route('doctors') }}" class="btn btn-sm btn-light mc-btn d-none d-sm-inline-block">All doctors
                    <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="row g-3">
                @foreach ($specialities as $s)
                    <div class="col-6 col-md-4 col-lg-3 mc-reveal">
                        <a class="mc-spec c{{ ($loop->index % 6) + 1 }}"
                            href="{{ route('doctors', ['speciality' => $s->id]) }}">
                            <span class="ic"><i class="bi bi-{{ $s->icon }}"></i></span>
                            <span class="nm">{{ $s->name }}</span>
                            <i class="bi bi-arrow-right-short go"></i>
                        </a>
                    </div>
                @endforeach
            </div>
        </section>

    </div>{{-- /mc-wrap --}}

    {{-- ================= DOCTORS (full-width band, sliding cards) ================= --}}
    @php
        $slotLabel = function ($sl) {
            if (!$sl) {
                return null;
            }
            $t = \Carbon\Carbon::parse($sl['date'] . ' ' . $sl['time'])->format('g:i A');
            $d = $sl['today']
                ? 'Today'
                : ($sl['tomorrow']
                    ? 'Tomorrow'
                    : \Carbon\Carbon::parse($sl['date'])->format('D, j M'));
            return $d . ' · ' . $t;
        };
    @endphp
    <section class="mc-docs-band">
        <div class="mc-wrap">
            <div class="mc-docs-head mc-reveal">
                <div>
                    <span class="mc-eyebrow2">Our doctors</span>
                    <h2>Doctors available <span>to book</span></h2>
                    <p>Meet our verified doctors who are accepting appointments</p>
                </div>
                <div class="mc-docs-nav">
                    <button type="button" class="mc-arrow" id="docPrev" aria-label="Previous doctors"><i
                            class="bi bi-arrow-left"></i></button>
                    <button type="button" class="mc-arrow" id="docNext" aria-label="Next doctors"><i
                            class="bi bi-arrow-right"></i></button>
                    <a href="{{ route('doctors') }}" class="btn btn-primary mc-btn mc-all">View all doctors <i
                            class="bi bi-arrow-right"></i></a>
                </div>
            </div>

            @if ($featured->isEmpty())
                <div class="mc-empty"><i class="bi bi-person-badge"></i>
                    <p class="mt-2 mb-0 text-secondary">No doctors are listed yet.</p>
                </div>
            @else
                <div class="mc-docs-track" id="docTrack" tabindex="0" aria-label="Doctors available to book">
                    @foreach ($featured->chunk(6) as $page)
                        <div class="mc-docs-page">
                            @foreach ($page as $d)
                                @php $sl = $d->next_slot; @endphp
                                <article class="mc-dc c{{ ($d->speciality_id % 6) + 1 }}">
                                    <a class="mc-dc-ph" href="{{ route('doctors.show', $d) }}" tabindex="-1"
                                        aria-hidden="true">
                                        @if ($d->photo_url)
                                            <span class="ph-bg"
                                                style="background-image:url('{{ $d->photo_url }}')"></span>
                                            <img src="{{ $d->photo_url }}" alt="{{ $d->user->name }}" loading="lazy">
                                        @else
                                            <div class="mc-ph-init mc-dc-init">{{ $d->user->initials }}</div>
                                        @endif
                                        @if ($sl && $sl['today'])
                                            <span class="mc-avail"><i></i> Available Today</span>
                                        @elseif ($sl)
                                            <span class="mc-avail soon"><i></i>
                                                {{ $sl['tomorrow'] ? 'Tomorrow' : \Carbon\Carbon::parse($sl['date'])->format('D, j M') }}</span>
                                        @else
                                            <span class="mc-avail off"><i></i> No slots soon</span>
                                        @endif
                                    </a>
                                    <div class="mc-dc-body">
                                        <span class="mc-dc-tag"><b class="ic"><i
                                                    class="bi bi-{{ $d->speciality->icon }}"></i></b><em>{{ $d->speciality->name }}</em></span>
                                        <h3><a href="{{ route('doctors.show', $d) }}">{{ $d->user->name }}</a></h3>
                                        <div class="mc-dc-q">{{ $d->qualification ?: $d->designation }}</div>
                                        <div class="mc-dc-meta">
                                            @if (($d->reviews_count ?? 0) > 0)
                                                <span><i class="bi bi-star-fill star"></i>
                                                    <b>{{ number_format($d->reviews_avg_rating, 1) }}</b>
                                                    <small>({{ $d->reviews_count }} reviews)</small></span>
                                            @else
                                                <span><i class="bi bi-star-fill star"></i> <b>New</b></span>
                                            @endif
                                            <span><i class="bi bi-briefcase"></i> {{ $d->experience_years }} yrs
                                                exp</span>
                                            <span><i class="bi bi-geo-alt"></i> {{ $d->city->name }}</span>
                                        </div>
                                        @if ($sl)
                                            <a class="mc-next"
                                                href="{{ route('doctors.show', ['doctor' => $d, 'date' => $sl['date']]) }}">
                                                <i class="bi bi-calendar2-check"></i>
                                                <span><small>Next available</small><b>{{ $slotLabel($sl) }}</b></span>
                                                <i class="bi bi-chevron-right go"></i>
                                            </a>
                                        @else
                                            <a class="mc-next off" href="{{ route('doctors.show', $d) }}">
                                                <i class="bi bi-calendar2-x"></i>
                                                <span><small>Next available</small><b>See schedule</b></span>
                                                <i class="bi bi-chevron-right go"></i>
                                            </a>
                                        @endif
                                        <div class="mc-dc-foot">
                                            <div class="price">&#8377;{{ number_format($d->fees) }} <small>/
                                                    visit</small></div>
                                            <a href="{{ route('doctors.show', $d) }}" class="btn btn-primary mc-btn">Book
                                                Appointment</a>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <div class="mc-wrap">
        {{-- ================= HOW IT WORKS ================= --}}
        <section class="mc-sec">
            <div class="text-center mb-4 mc-reveal"><span class="mc-eyebrow">How it works</span>
                <h2 class="mc-title mt-1">Book your visit in 3 easy steps</h2>
                <div class="mc-sub">No calls, no waiting in queues</div>
            </div>
            <div class="row g-4 mc-steps">
                <div class="col-md-4 mc-reveal">
                    <div class="mc-step"><span class="n">1</span>
                        <div class="ic"><i class="bi bi-search-heart"></i></div>
                        <h3>Find a doctor</h3>
                        <p>Search by name, speciality or city and compare experience, fees and ratings.</p>
                    </div>
                </div>
                <div class="col-md-4 mc-reveal">
                    <div class="mc-step"><span class="n">2</span>
                        <div class="ic"><i class="bi bi-calendar2-week"></i></div>
                        <h3>Pick a time slot</h3>
                        <p>See the days and times the doctor is really available and choose what suits you.</p>
                    </div>
                </div>
                <div class="col-md-4 mc-reveal">
                    <div class="mc-step"><span class="n">3</span>
                        <div class="ic"><i class="bi bi-check2-circle"></i></div>
                        <h3>Get confirmed</h3>
                        <p>Your request goes straight to the doctor. Track it from your dashboard anytime.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ================= WHY MEDICARE ================= --}}
        <section class="mc-why mc-reveal">
            <div class="row align-items-center g-4 position-relative">
                <div class="col-lg-4">
                    <span class="mc-eyebrow light">Why MediCare</span>
                    <h2 class="mt-1 mb-2">Care you can trust, made simple</h2>
                    <p class="mb-0">Everything you need to choose a doctor with confidence and manage your visits in one
                        place.</p>
                </div>
                <div class="col-lg-8">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="mc-feat"><i class="bi bi-patch-check-fill"></i>
                                <div><b>Verified doctors</b><span>Licence and hospital are checked by our admin before a
                                        doctor
                                        goes live.</span></div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mc-feat"><i class="bi bi-lightning-charge-fill"></i>
                                <div><b>Instant booking</b><span>Choose a slot and send your request in under a
                                        minute.</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mc-feat"><i class="bi bi-star-fill"></i>
                                <div><b>Real patient reviews</b><span>Only patients with a completed visit can rate a
                                        doctor.</span></div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mc-feat"><i class="bi bi-shield-lock-fill"></i>
                                <div><b>Private &amp; secure</b><span>Your profile, visits and receipts stay in your own
                                        account.</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ================= PATIENT STORIES (hidden when there are no reviews) ================= --}}
        @if ($reviews->count())
            @php
                // repeat the list until a strip is long enough to loop smoothly
                $rowA = $reviews;
                while ($rowA->count() < 6) {
                    $rowA = $rowA->concat($reviews);
                }
                $rowB = $rowA->reverse()->values();
                $rows = $reviews->count() >= 4 ? [[$rowA, ''], [$rowB, 'rev']] : [[$rowA, '']];
                $dist = $ratingSummary['dist'] ?? [];
                $total = max(1, (int) ($ratingSummary['count'] ?? 0));
            @endphp
    </div>{{-- /mc-wrap --}}
    <section class="mc-stories">
        <span class="mc-stories-orb o1"></span><span class="mc-stories-orb o2"></span>
        <div class="mc-wrap">
            <div class="mc-stories-head mc-reveal">
                <div class="mc-stories-title">
                    <span class="mc-eyebrow2">Patient stories</span>
                    <h2>What patients say <span>about their care</span></h2>
                    <p>Honest feedback, only from patients with a completed visit</p>
                </div>
                <div class="mc-rate-card">
                    <div class="big">
                        <b>{{ number_format($ratingSummary['avg'] ?? 0, 1) }}</b>
                        @include('partials.stars', ['v' => $ratingSummary['avg'] ?? 0])
                        <small><span data-count="{{ $ratingSummary['count'] ?? 0 }}">0</span> verified reviews</small>
                    </div>
                    <div class="bars">
                        @foreach ([5, 4, 3, 2, 1] as $st)
                            <div class="bar"><span>{{ $st }}<i class="bi bi-star-fill"></i></span><em><i
                                        style="--w:{{ round((($dist[$st] ?? 0) / $total) * 100) }}%"></i></em></div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        @foreach ($rows as [$list, $dir])
            <div class="mc-marquee mc-marquee-2 {{ $dir }}" aria-label="Patient stories">
                @foreach ([false, true] as $dup)
                    <div class="mc-marquee-group" @if ($dup) aria-hidden="true" @endif>
                        @foreach ($list as $rv)
                            @php $pPhoto = $rv->patient?->patientProfile?->photo_url; @endphp
                            <figure class="mc-q2">
                                <span class="mark" aria-hidden="true"><i class="bi bi-chat-heart-fill"></i></span>
                                <div class="top">
                                    @include('partials.stars', ['v' => $rv->rating])
                                    <span class="ver"><i class="bi bi-patch-check-fill"></i> Verified visit</span>
                                </div>
                                <blockquote>{{ $rv->comment }}</blockquote>
                                @if (!empty($rv->tags))
                                    <div class="tg">
                                        @foreach (array_slice($rv->tags, 0, 3) as $tg)
                                            <span>{{ $tg }}</span>
                                        @endforeach
                                    </div>
                                @endif
                                <figcaption>
                                    <span class="av">{{ mb_strtoupper(mb_substr($rv->reviewer, 0, 1)) }}@if ($pPhoto)
                                            <img src="{{ $pPhoto }}" alt="{{ $rv->reviewer }}" loading="lazy"
                                                onerror="this.remove()">
                                        @endif
                                    </span>
                                    <div><b>{{ $rv->reviewer }}</b><span>visited {{ $rv->doctor->user->name }} ·
                                            {{ $rv->doctor->speciality->name }}</span></div>
                                    <small>{{ $rv->created_at->diffForHumans(null, true) }} ago</small>
                                </figcaption>
                            </figure>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endforeach
    </section>
    <div class="mc-wrap">
        @endif

        {{-- ================= FAQ ================= --}}
        @php
            $faqs = [
                [
                    'bi-calendar2-check',
                    'How do I book an appointment?',
                    'Open a doctor\'s profile, choose a date and an available time slot, and send the booking. You need a patient account to book.',
                ],
                [
                    'bi-patch-check-fill',
                    'Are the doctors verified?',
                    'Yes. Every doctor who registers is reviewed by our admin team (licence and hospital details) before the profile is listed publicly.',
                ],
                [
                    'bi-x-circle',
                    'Can I cancel my appointment?',
                    'Yes. Go to My appointments in your dashboard and cancel any upcoming appointment.',
                ],
                [
                    'bi-star-fill',
                    'Who can rate a doctor?',
                    'Only patients whose visit has been marked completed can leave a rating and review, so feedback comes from real visits.',
                ],
                [
                    'bi-currency-rupee',
                    'How much does a consultation cost?',
                    'Each doctor sets their own fee, shown on their card and profile before you book. You can also filter doctors by maximum fees.',
                ],
                [
                    'bi-camera-video',
                    'Can I consult a doctor online?',
                    'Some doctors offer online consultations or both clinic and online visits. The visit type is shown on the doctor\'s profile while booking.',
                ],
            ];
        @endphp
        <section class="mc-sec">
            <div class="mc-faq-wrap">
                <div class="mc-faq-side mc-reveal">
                    <span class="mc-eyebrow light">FAQ</span>
                    <h2>Questions? We have answers</h2>
                    <p>Can't find what you need? Browse the doctors and check their profile for details.</p>
                    <a href="{{ route('doctors') }}" class="btn btn-light mc-btn"><i class="bi bi-search"></i> Browse
                        doctors</a>
                    <i class="bi bi-question-circle-fill mc-faq-bg"></i>
                </div>
                <div class="mc-faq2 mc-reveal" id="faq">
                    @foreach ($faqs as [$ic, $q, $a])
                        <div class="mc-faq2-item">
                            <button class="mc-faq2-q" type="button" data-bs-toggle="collapse"
                                data-bs-target="#faq{{ $loop->index }}"
                                aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                                <span class="ic"><i class="bi {{ $ic }}"></i></span>
                                <span class="t">{{ $q }}</span>
                                <span class="pm"><i class="bi bi-plus-lg"></i></span>
                            </button>
                            <div id="faq{{ $loop->index }}" class="collapse {{ $loop->first ? 'show' : '' }}"
                                data-bs-parent="#faq">
                                <div class="mc-faq2-a">{{ $a }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ================= CTA ================= --}}
        <section class="mc-cta mc-reveal">
            <div class="mc-blob b1"></div>
            <div class="mc-blob b2"></div>
            <div class="position-relative">
                <h2>Ready to see a doctor?</h2>
                <p>Find a trusted specialist near you and book your slot today.</p>
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <a href="{{ route('doctors') }}" class="btn btn-light mc-btn px-4"><i class="bi bi-search"></i> Find
                        a
                        doctor</a>
                    @guest
                        <a href="{{ route('register') }}" class="btn btn-glass mc-btn px-4">Create an account</a>
                        <a href="{{ route('register.doctor') }}" class="btn btn-glass mc-btn px-4">Join as a doctor</a>
                    @endguest
                </div>
            </div>
        </section>
    </div>{{-- /mc-wrap --}}
@endsection
    