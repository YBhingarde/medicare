<?php
namespace App\Http\Controllers;

use App\Models\{Appointment, Review};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    /** Patient rates a completed visit (also used to edit an earlier review). */
    public function store(Request $r, Appointment $appointment)
    {
        abort_unless($appointment->patient_id === $r->user()->id, 403);
        abort_unless($appointment->status === 'completed', 403, 'You can rate a doctor only after the visit is completed.');
        $d = $r->validate([
            'rating' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:500',
            'tags' => 'nullable|array|max:6',
            'tags.*' => ['string', Rule::in(Review::TAGS)],
        ], ['rating.required' => 'Please tap a star to rate your visit.']);

        Review::updateOrCreate(['appointment_id' => $appointment->id], [
            'doctor_id' => $appointment->doctor_id, 'patient_id' => $r->user()->id,
            'rating' => $d['rating'], 'comment' => $d['comment'] ?? null, 'tags' => array_values($d['tags'] ?? []),
        ]);
        return back()->with('ok', 'Thank you! Your feedback helps other patients choose better.')->with('reviewed', true);
    }

    /** "Don't ask again" on the pop-up. Plain query so the appointment/transaction hooks are not triggered. */
    public function skip(Request $r, Appointment $appointment)
    {
        abort_unless($appointment->patient_id === $r->user()->id, 403);
        Appointment::whereKey($appointment->id)->update(['review_skipped' => true]);
        return back();
    }

    /** Polled by the patient panel so the pop-up appears right after the doctor completes a visit. */
    public function pending(Request $r)
    {
        $a = Review::claimPending($r->user()->id); // shown once only: the visit is marked as offered
        return response()->json($a ? [
            'id' => $a->id, 'doctor' => $a->doctor->user->name, 'spec' => $a->doctor->speciality->name,
            'date' => $a->date->format('d M Y'), 'photo' => $a->doctor->photo_url, 'init' => $a->doctor->user->initials,
        ] : null);
    }

    /** Doctor: all feedback received. */
    public function doctorIndex(Request $r)
    {
        $doc = $r->user()->doctor;
        abort_unless($doc, 403, 'No doctor profile is linked to this account.');
        $q = $doc->reviews()->with('patient')->latest();
        if ($r->filled('stars')) $q->where('rating', (int) $r->stars);
        return view('doctor.reviews', ['reviews' => $q->paginate(10)->withQueryString(), 'summary' => Review::summary($doc->id)]);
    }

    /** Admin: every review, with hide / delete. */
    public function adminIndex(Request $r)
    {
        $q = Review::with('doctor.user', 'patient')->latest();
        if ($r->filled('stars')) $q->where('rating', (int) $r->stars);
        if ($r->filled('doctor')) $q->where('doctor_id', (int) $r->doctor);
        return view('admin.reviews', [
            'reviews' => $q->paginate(12)->withQueryString(),
            'summary' => Review::summary(),
            'hidden' => Review::where('is_visible', false)->count(),
            'doctors' => \App\Models\Doctor::with('user')->get()->sortBy(fn ($d) => $d->user->name),
        ]);
    }

    public function toggle(Review $review)
    {
        $review->update(['is_visible' => ! $review->is_visible]);
        return back()->with('ok', $review->is_visible ? 'Review is visible again.' : 'Review hidden from the website.');
    }

    public function destroy(Review $review)
    {
        $review->delete();
        return back()->with('ok', 'Review deleted.');
    }
}
