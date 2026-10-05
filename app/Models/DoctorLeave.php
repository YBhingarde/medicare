<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorLeave extends Model
{
    protected $guarded = [];
    protected $casts = ['date' => 'date'];

    public function doctor() { return $this->belongsTo(Doctor::class); }
}
