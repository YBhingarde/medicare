<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientProfile extends Model
{
    protected $guarded = [];
    protected $casts = ['dob' => 'date', 'temperature' => 'float', 'weight' => 'float', 'vitals_updated_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
    public function preferredDoctor() { return $this->belongsTo(Doctor::class, 'preferred_doctor_id'); }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? asset($this->photo) : null;
    }

    /** 0-100: how much of the profile is filled in. */
    public function completion(): int
    {
        $fields = [$this->user?->phone, $this->dob, $this->gender, $this->blood_group, $this->address, $this->city,
            $this->pincode, $this->emergency_phone, $this->photo];
        return (int) round(collect($fields)->filter(fn ($v) => filled($v))->count() / count($fields) * 100);
    }
}
