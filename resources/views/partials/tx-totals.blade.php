{{-- Paid / Pending / Refunded cards (each one opens the list filtered by that status). Params: $route, $totals, $labels = [paid, pending, refunded] --}}
@php $l = $labels ?? ['Paid', 'Pending', 'Refunded']; @endphp
<div class="row g-3 mb-3">
  @foreach ([[$l[0], $totals['paid'], 'check2-circle', 'green', 'paid'], [$l[1], $totals['pending'], 'hourglass-split', 'amber', 'pending'], [$l[2], $totals['refunded'], 'arrow-counterclockwise', 'blue', 'refunded']] as $i => [$lb, $v, $ic, $c, $st])
    <div class="col-sm-4">@include('partials.stat-card', ['label' => $lb, 'value' => $v, 'icon' => $ic, 'color' => $c, 'prefix' => '₹', 'delay' => $i * 80, 'url' => route($route, ['status' => $st])])</div>
  @endforeach
</div>
