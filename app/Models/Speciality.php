<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Speciality extends Model
{
    protected $guarded = [];
    public function doctors() { return $this->hasMany(Doctor::class); }

    /** Bootstrap-icons name (without "bi-"). Every name here exists in bootstrap-icons 1.11.
     *  NOTE: "stethoscope" does NOT exist in Bootstrap Icons - that was why General Physician had no logo. */
    public function getIconAttribute(): string
    {
        $n = strtolower(trim($this->name));
        $map = [
            'cardio' => 'heart-pulse-fill', 'dent' => 'emoji-smile-fill', 'derma' => 'droplet-half',
            'physician' => 'clipboard2-pulse-fill', 'general' => 'clipboard2-pulse-fill',
            'gyne' => 'gender-female', 'ortho' => 'bandaid-fill', 'pedia' => 'balloon-heart-fill',
            'neuro' => 'activity', 'ophth' => 'eye-fill', 'eye' => 'eye-fill', 'psych' => 'chat-heart-fill',
            'pulmo' => 'lungs',
        ];
        foreach ($map as $k => $i) if (str_contains($n, $k)) return $i;
        if ($n === 'ent' || str_contains($n, 'otolaryn')) return 'soundwave';
        return 'plus-circle-fill';
    }
}