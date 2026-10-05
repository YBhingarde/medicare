<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    public const TAGS = ['Good listener', 'Clear explanation', 'On time', 'Friendly', 'Thorough check-up', 'Worth the fee'];
    public const LABELS = [1 => 'Poor', 2 => 'Fair', 3 => 'Good', 4 => 'Very good', 5 => 'Excellent'];

    protected $guarded = [];
    protected $casts = ['tags' => 'array', 'is_visible' => 'boolean'];

    public function doctor() { return $this->belongsTo(Doctor::class); }
    public function patient() { return $this->belongsTo(User::class, 'patient_id'); }
    public function appointment() { return $this->belongsTo(Appointment::class); }

    /** The rating pop-up is offered only for a visit completed this recently (older visits can still be rated with the "Rate doctor" button). */
    public const PROMPT_WITHIN_DAYS = 3;

    /** Newest recently completed visit of this patient that still waits for a review, was not skipped and was not offered yet. */
    public static function pendingFor(int $patientId): ?Appointment
    {
        return Appointment::with('doctor.user', 'doctor.speciality')
            ->where('patient_id', $patientId)->where('status', 'completed')->where('review_skipped', false)
            ->whereNull('review_prompted_at')
            ->where('updated_at', '>=', now()->subDays(self::PROMPT_WITHIN_DAYS))
            ->whereDoesntHave('review')->orderByDesc('updated_at')->first();
    }

    /**
     * Same as pendingFor(), but marks the visit as "offered" so the pop-up is shown only ONCE for it.
     * The update is a plain query (no model events, updated_at untouched) and only one request can win it.
     */
    public static function claimPending(int $patientId): ?Appointment
    {
        $a = static::pendingFor($patientId);
        if (! $a) return null;
        $won = \Illuminate\Support\Facades\DB::table('appointments')->where('id', $a->id)->whereNull('review_prompted_at')
            ->update(['review_prompted_at' => now()]);
        return $won ? $a : null;
    }

    /** "Rahul Deshmukh" -> "Rahul D." (public pages never show a full patient name). */
    public function getReviewerAttribute(): string
    {
        $p = preg_split('/\s+/', trim($this->patient?->name ?? 'Patient'));
        return $p[0] . (count($p) > 1 ? ' ' . mb_strtoupper(mb_substr(end($p), 0, 1)) . '.' : '');
    }

    /** Average, count and 5..1 star distribution of the visible reviews of a doctor (or of everyone when null). */
    public static function summary(?int $doctorId = null): array
    {
        $q = static::where('is_visible', true);
        if ($doctorId) $q->where('doctor_id', $doctorId);
        $rows = $q->selectRaw('rating, count(*) as c')->groupBy('rating')->pluck('c', 'rating');
        $dist = []; $count = 0; $sum = 0;
        foreach ([5, 4, 3, 2, 1] as $s) {
            $n = (int) ($rows[$s] ?? 0);
            $dist[$s] = $n; $count += $n; $sum += $n * $s;
        }
        return ['avg' => $count ? round($sum / $count, 1) : 0, 'count' => $count, 'dist' => $dist];
    }
}
