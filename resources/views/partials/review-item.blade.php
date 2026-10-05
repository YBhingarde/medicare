{{-- One review. Expects $rv (Review with patient). Optional $full = show full patient name, $showDoctor = show doctor name --}}
<div class="rv-item {{ $rv->is_visible ? '' : 'rv-hidden' }}">
    <span class="rv-av">{{ $rv->patient?->initials ?? 'P' }}</span>
    <div class="rv-body">
        <div class="rv-top"><b>{{ $full ?? false ? $rv->patient?->name ?? 'Patient' : $rv->reviewer }}</b>
            @include('partials.stars', ['v' => $rv->rating, 'cls' => 'sm'])
            <small>{{ $rv->created_at->diffForHumans() }}</small>
            @if (!$rv->is_visible)
                <span class="badge text-bg-secondary">Hidden</span>
            @endif
        </div>
        @if (!empty($showDoctor))
            <div class="small text-secondary">for {{ $rv->doctor->user->name }}</div>
        @endif
        @if ($rv->comment)
            <p class="rv-text">{{ $rv->comment }}</p>
        @endif
        @if (!empty($rv->tags))
            <div class="rv-tags">
                @foreach ($rv->tags as $t)
                    <span>{{ $t }}</span>
                @endforeach
            </div>
        @endif
    </div>
</div>
