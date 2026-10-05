{{-- "Rate" / "Edit review" button for a completed appointment. Expects $a (Appointment with doctor.user, doctor.speciality, review). --}}
@if($a->status === 'completed')
@php $rv = $a->review; @endphp
<button type="button" class="btn btn-sm {{ $rv ? 'btn-light border' : 'btn-outline-primary' }}" data-rate="{{ $a->id }}"
    data-doctor="{{ $a->doctor->user->name }}" data-spec="{{ $a->doctor->speciality->name }}" data-date="{{ $a->date->format('d M Y') }}"
    data-photo="{{ $a->doctor->photo_url }}" data-init="{{ $a->doctor->user->initials }}"
    @if($rv) data-rating="{{ $rv->rating }}" data-comment="{{ $rv->comment }}" data-tags="{{ json_encode($rv->tags ?? []) }}" @endif>
    @if($rv)@include('partials.stars', ['v' => $rv->rating, 'cls' => 'sm']) <span class="ms-1">Edit</span>@else<i class="bi bi-star"></i> Rate doctor @endif
</button>
@endif