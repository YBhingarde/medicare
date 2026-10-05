<?php
namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    public const DAYS = ['mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday'];

    protected $guarded = [];
    protected $casts = [
        'approved' => 'boolean', 'dob' => 'date',
        'languages' => 'array', 'education' => 'array', 'awards' => 'array',
        'certifications' => 'array', 'availability' => 'array',
        'submitted_at' => 'datetime', 'verified_at' => 'datetime',
    ];

    /** Private verification documents: form field / url segment => column */
    public const DOCS = ['license' => 'license_document', 'id' => 'id_document', 'hospital' => 'hospital_document'];

    public function user() { return $this->belongsTo(User::class); }
    public function speciality() { return $this->belongsTo(Speciality::class); }
    public function city() { return $this->belongsTo(City::class); }
    public function hospital() { return $this->belongsTo(Hospital::class); }
    public function appointments() { return $this->hasMany(Appointment::class); }
    public function leaves() { return $this->hasMany(DoctorLeave::class); }
    /** Visible reviews only (hidden ones are moderated away by the admin). */
    public function reviews() { return $this->hasMany(Review::class)->where('is_visible', true); }
    public function allReviews() { return $this->hasMany(Review::class); }
    public function verifier() { return $this->belongsTo(User::class, 'verified_by'); }

    public function getIsVerifiedAttribute(): bool
    {
        return $this->verification_status === 'approved';
    }

    /** Photo URL, or null when no photo was uploaded (views then show a gradient initials avatar). */
    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? asset($this->photo) : null;
    }


    /** First free slot within the next few days: ['date','time','today','tomorrow'] or null. */
    public function nextSlot(int $days = 10): ?array
    {
        $max = min($days, max(1, (int) ($this->advance_days ?: $days)));
        for ($i = 0; $i <= $max; $i++) {
            $date = now()->addDays($i)->toDateString();
            $slots = $this->slotsFor($date);
            if ($slots) return ['date' => $date, 'time' => $slots[0], 'today' => $i === 0, 'tomorrow' => $i === 1];
        }
        return null;
    }

    /** Upcoming leave days as 'Y-m-d' strings (loaded once per request; uses the eager-loaded leaves when available). */
    protected ?array $leaveDatesCache = null;

    /** Active bookings per time, filled in bulk by preloadSlots(): ['2026-10-05' => ['10:00' => 1, ...], ...]. */
    protected array $bookedCache = [];

    public function leaveDates(): array
    {
        if ($this->leaveDatesCache !== null) return $this->leaveDatesCache;
        $today = now()->toDateString();
        $leaves = $this->relationLoaded('leaves')
            ? $this->leaves
            : $this->leaves()->whereDate('date', '>=', $today)->get();
        return $this->leaveDatesCache = $leaves
            ->map(fn ($l) => Carbon::parse($l->date)->toDateString())
            ->filter(fn ($d) => $d >= $today)->values()->all();
    }

    /**
     * Loads leaves and bookings for MANY doctors with 2 queries (today .. today + $days), so nextSlot() / slotsFor()
     * on a list page do not run 2 queries per doctor. Call it once on the collection before looping.
     */
    public static function preloadSlots(iterable $doctors, int $days = 10): void
    {
        $doctors = $doctors instanceof EloquentCollection ? $doctors : new EloquentCollection(collect($doctors)->all());
        if ($doctors->isEmpty()) return;

        $from = now()->toDateString();
        $to = now()->addDays($days)->toDateString();
        $doctors->loadMissing(['leaves' => fn ($q) => $q->whereDate('date', '>=', $from)]);

        $grouped = Appointment::whereIn('doctor_id', $doctors->modelKeys())
            ->whereDate('date', '>=', $from)->whereDate('date', '<=', $to)
            ->whereNotIn('status', ['cancelled', 'expired'])
            ->get(['doctor_id', 'date', 'time'])->groupBy('doctor_id');

        foreach ($doctors as $d) {
            $byDate = ($grouped[$d->id] ?? collect())->groupBy(fn ($a) => $a->date->toDateString());
            for ($i = 0; $i <= $days; $i++) {
                $date = now()->addDays($i)->toDateString();
                $d->bookedCache[$date] = isset($byDate[$date])
                    ? $byDate[$date]->pluck('time')->map(fn ($t) => substr((string) $t, 0, 5))->countBy()->all()
                    : [];
            }
        }
    }

    public function isOnLeave(string $date): bool
    {
        return in_array(Carbon::parse($date)->toDateString(), $this->leaveDates(), true);
    }

    /** Working windows [[from,to],...] for a given date. Falls back to slot_start/slot_end when no weekly schedule is saved. */
    public function windowsFor(string $date): array
    {
        if ($this->isOnLeave($date)) return []; // doctor is on leave: no slots, so no bookings
        if (empty($this->availability)) return [[$this->slot_start, $this->slot_end]];
        $key = strtolower(Carbon::parse($date)->format('D')); // mon, tue ...
        return $this->availability[$key] ?? [];
    }

    /** Patients allowed per time slot (at least 1). */
    public function capacity(): int
    {
        return max(1, (int) ($this->slot_capacity ?: 1));
    }

    /** Active (not cancelled / expired) bookings per time (HH:MM) for a date: ['10:00' => 2, ...]. */
    public function bookedCounts(string $date): array
    {
        return $this->bookedCache[$date] ?? $this->appointments()->whereDate('date', $date)->whereNotIn('status', ['cancelled', 'expired'])
            ->pluck('time')->map(fn ($t) => substr($t, 0, 5))->countBy()->all();
    }

    /** Every upcoming slot of a date with its state: [['time','booked','left','full'], ...]. Full slots stay in the list so patients see them. */
    public function slotBoard(string $date): array
    {
        $counts = $this->bookedCounts($date);
        $cap = $this->capacity();
        $board = [];
        foreach ($this->windowsFor($date) as [$from, $to]) {
            $t = Carbon::parse("$date $from");
            $end = Carbon::parse("$date $to");
            while ($t < $end) {
                if ($t->isFuture()) {
                    $time = $t->format('H:i');
                    $booked = $counts[$time] ?? 0;
                    $board[$time] = ['time' => $time, 'booked' => $booked, 'left' => max(0, $cap - $booked), 'full' => $booked >= $cap];
                }
                $t->addMinutes(max(5, (int) $this->slot_minutes));
            }
        }
        ksort($board);
        return array_values($board);
    }

    /** Slots (HH:MM) that still have room on a date. */
    public function slotsFor(string $date): array
    {
        return array_values(array_map(fn ($s) => $s['time'], array_filter($this->slotBoard($date), fn ($s) => ! $s['full'])));
    }
}
