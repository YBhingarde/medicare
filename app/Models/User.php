<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'phone', 'email_verified_at'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed'];
    }

    public function doctor() { return $this->hasOne(Doctor::class); }
    public function appointments() { return $this->hasMany(Appointment::class, 'patient_id'); }
    public function patientProfile() { return $this->hasOne(PatientProfile::class); }
    public function transactions() { return $this->hasMany(Transaction::class, 'patient_id'); }

    /** Patient ID shown on the profile, e.g. PT0025. */
    public function getPatientCodeAttribute(): string
    {
        return 'PT' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    /** Profile photo for the header avatar: the doctor's photo or the patient's profile photo (null = show initials). */
    public function getPhotoUrlAttribute(): ?string
    {
        return match ($this->role) {
            'doctor' => $this->doctor?->photo_url,
            'patient' => $this->patientProfile?->photo_url,
            default => null,
        };
    }

    /** Two-letter initials for avatars ("Dr. Anil Kulkarni" -> "AK"). */
    public function getInitialsAttribute(): string
    {
        $name = preg_replace('/^dr\.?\s+/i', '', trim($this->name));
        return mb_strtoupper(collect(preg_split('/\s+/', $name))->filter()->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode(''));
    }
}
