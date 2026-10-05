{{-- Patient rating pop-up. Included once from layouts/admin for patients.
     Opens by itself for the latest completed visit that has no review yet,
     and can be opened for any visit with a button that has data-rate="{appointment id}". --}}
@php
    $rmUser = auth()->user();
    $rmPrompt = null;
    if ($rmUser && $rmUser->role === 'patient') {
        $rmPrompt = \App\Models\Review::claimPending($rmUser->id); // offered once per visit
    }
@endphp
<div class="modal fade mc-rate" id="rateModal" tabindex="-1" aria-labelledby="rateTitle" aria-hidden="true"
    data-url="{{ url('/my-appointments/__ID__/review') }}"
    data-skip-url="{{ url('/my-appointments/__ID__/review/skip') }}" data-poll="{{ route('patient.review.pending') }}"
    @if ($rmPrompt) data-prompt="{{ $rmPrompt->id }}" data-p-doctor="{{ $rmPrompt->doctor->user->name }}" data-p-spec="{{ $rmPrompt->doctor->speciality->name }}"
       data-p-date="{{ $rmPrompt->date->format('d M Y') }}" data-p-photo="{{ $rmPrompt->doctor->photo_url }}" data-p-init="{{ $rmPrompt->doctor->user->initials }}" @endif>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="rate-head">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
                <span class="rate-doc"><img id="rateImg" alt="" hidden><b id="rateInit">DR</b></span>
                <div class="rate-sub">How was your visit?</div>
                <h5 id="rateTitle" class="rate-name">Your doctor</h5>
                <div class="rate-meta" id="rateMeta"></div>
            </div>
            <form method="post" id="rateForm" action="#">
                @csrf
                <div class="modal-body">
                    <div class="rate-face" id="rateFace" aria-hidden="true">🙂</div>
                    <div class="rate-stars" id="rateStars" role="radiogroup" aria-label="Rating">
                        @for ($i = 1; $i <= 5; $i++)
                            <button type="button" class="rate-star" data-v="{{ $i }}" role="radio"
                                aria-checked="false" aria-label="{{ $i }} star{{ $i > 1 ? 's' : '' }}"><i
                                    class="bi bi-star-fill"></i></button>
                        @endfor
                    </div>
                    <div class="rate-word" id="rateWord">Tap a star to rate</div>
                    <input type="hidden" name="rating" id="rateInput" value="">
                    <div class="rate-tags" id="rateTags">
                        @foreach (\App\Models\Review::TAGS as $t)
                            <label class="rate-tag"><input type="checkbox" name="tags[]"
                                    value="{{ $t }}"><span>{{ $t }}</span></label>
                        @endforeach
                    </div>
                    <div class="position-relative">
                        <textarea name="comment" id="rateComment" class="form-control" rows="3" maxlength="500"
                            placeholder="Share a few words about your experience (optional)"></textarea>
                        <small class="rate-count"><span id="rateCount">0</span>/500</small>
                    </div>
                </div>
                <div class="modal-footer rate-foot">
                    <button type="button" class="btn btn-link text-secondary text-decoration-none btn-sm"
                        data-bs-dismiss="modal">Maybe later</button>
                    <button type="submit" class="btn btn-primary" id="rateSubmit" disabled><i class="bi bi-send"></i>
                        Submit review</button>
                </div>
            </form>
            <form method="post" id="rateSkip" action="#" class="text-center pb-3 mt-n2">@csrf
                <button class="btn btn-link btn-sm text-secondary p-0 small" style="font-size:.72rem">Don't ask me again
                    for this visit</button>
            </form>
        </div>
    </div>
</div>
@if (session('reviewed'))
    <script>
        window.addEventListener('load', function() {
            if (window.mcConfetti) mcConfetti();
        });
    </script>
@endif
