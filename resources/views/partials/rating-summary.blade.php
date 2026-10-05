{{-- Average + 5..1 distribution. Expects $summary = Review::summary() --}}
<div class="rv-sum">
  <div class="rv-big"><b data-count="{{ $summary['avg'] }}" data-dec="1">{{ number_format($summary['avg'], 1) }}</b>
    @include('partials.stars', ['v' => $summary['avg'], 'cls' => 'lg'])<small>{{ $summary['count'] }} {{ \Illuminate\Support\Str::plural('review', $summary['count']) }}</small></div>
  <div class="rv-dist">
    @foreach($summary['dist'] as $star => $n)
      <div class="rv-row"><span>{{ $star }} <i class="bi bi-star-fill text-warning" style="font-size:.65rem"></i></span>
        <div class="rv-bar"><i style="--w:{{ $summary['count'] ? round($n / $summary['count'] * 100) : 0 }}%"></i></div><span>{{ $n }}</span></div>
    @endforeach
  </div>
</div>
