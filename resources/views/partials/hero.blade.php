{{-- Welcome banner. Params: $title, $sub, $chips = [[icon, text]], $actions = [[label, url, icon, solid]] --}}
<section class="hero fx">
  <span class="blob b1"></span><span class="blob b2"></span><span class="blob b3"></span>
  <svg class="ecg" viewBox="0 0 600 80" preserveAspectRatio="none" aria-hidden="true"><path d="M0 40H120l14 0 14-30 20 62 18-44 12 12h132l14 0 14-30 20 62 18-44 12 12H600"/></svg>
  <div class="hero-in">
    <div class="hero-txt">
      <div class="hero-hi"><span data-greet>Welcome back</span></div>
      <h2>{{ $title }}</h2>
      <p>{{ $sub }}</p>
      @if(!empty($chips))<div class="hero-chips">@foreach($chips as [$i, $t])<span class="hero-chip"><i class="bi bi-{{ $i }}"></i> {{ $t }}</span>@endforeach</div>@endif
    </div>
    @if(!empty($actions))<div class="hero-act">@foreach($actions as $a)<a class="btn btn-sm {{ ($a[3] ?? false) ? 'btn-light' : 'btn-glass' }}" href="{{ $a[1] }}"><i class="bi bi-{{ $a[2] }}"></i> {{ $a[0] }}</a>@endforeach</div>@endif
  </div>
</section>
