{{-- small round doctor avatar for tables. expects $d (Doctor) --}}
@if($d->photo_url)<img class="pp-avatar" src="{{ $d->photo_url }}" alt="{{ $d->user->name }}" loading="lazy">@else<span class="avatar-sm">{{ $d->user->initials }}</span>@endif
