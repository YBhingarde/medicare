{{-- expects $d (Doctor). Optional $cls for the fallback avatar. --}}
@if($d->photo_url)
  <img src="{{ $d->photo_url }}" alt="{{ $d->user->name }}" loading="lazy">
@else
  <div class="mc-ph-init {{ $cls ?? '' }}">{{ $d->user->initials }}</div>
@endif
