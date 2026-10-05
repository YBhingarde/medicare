@once
<style>
  .dash a.stat-a { display:block; color:inherit; text-decoration:none; height:100%; border-radius:var(--bs-card-border-radius, 14px); }
  .dash a.stat-a .stat { height:100%; cursor:pointer; transition:transform .25s, box-shadow .25s; }
  .dash a.stat-a:hover .stat { transform:translateY(-4px); box-shadow:0 14px 30px rgba(15,23,42,.12); }
  .dash a.stat-a:focus-visible { outline:2px solid var(--pc, #0d9488); outline-offset:3px; }
  .dash .stat .go { position:absolute; right:14px; bottom:12px; width:28px; height:28px; border-radius:50%; display:flex; align-items:center;
    justify-content:center; font-size:.9rem; background:rgba(var(--glow, 13,148,136), .14); color:var(--pc, #0d9488);
    opacity:0; transform:translateX(-6px); transition:opacity .25s, transform .25s; }
  .dash a.stat-a:hover .stat .go, .dash a.stat-a:focus-visible .stat .go { opacity:1; transform:none; }
</style>
@endonce
@isset($url)<a class="stat-a" href="{{ $url }}" title="{{ $label }}">@endisset
<div class="card stat fx stat-{{ $color ?? 'indigo' }}" style="--d:{{ $delay ?? 0 }}ms">
  <span class="stat-glow"></span>
  <div class="d-flex justify-content-between align-items-start">
    <div class="ico ico-{{ $color ?? 'indigo' }}"><i class="bi bi-{{ $icon }}"></i></div>
    @isset($delta)<span class="delta {{ $delta >= 0 ? 'up' : 'down' }}"><i class="bi bi-arrow-{{ $delta >= 0 ? 'up' : 'down' }}-short"></i>{{ abs($delta) }}%</span>@endisset
  </div>
  <div class="lbl">{{ $label }}</div>
  <div class="val"><span data-count="{{ $value }}" data-prefix="{{ $prefix ?? '' }}">{{ ($prefix ?? '') . number_format($value) }}</span></div>
  @isset($sub)<div class="sub">{{ $sub }}</div>@endisset
  @isset($url)<span class="go"><i class="bi bi-arrow-right"></i></span>@endisset
</div>
@isset($url)</a>@endisset
