{{-- Round avatar of a user: photo when there is one, otherwise gradient initials. Expects $u (User); optional $cls ("lg").
     The photo is laid over a fixed square box (position:absolute), so a tall portrait can never stretch the circle into an oval.
     The inline styles keep it round even if the stylesheet is cached or missing. --}}
@php $uaPhoto = $u->photo_url; @endphp
<span class="mc-um-av {{ $cls ?? '' }}" style="position:relative;overflow:hidden;border-radius:50%;aspect-ratio:1/1;align-self:center;flex-shrink:0">@if($uaPhoto)<img src="{{ $uaPhoto }}" alt="{{ $u->name }}" style="position:absolute;inset:0;display:block;width:100%;height:100%;object-fit:cover;object-position:50% 40%;border-radius:50%">@else{{ $u->initials ?: strtoupper(mb_substr(trim($u->name), 0, 1)) }}@endif</span>
