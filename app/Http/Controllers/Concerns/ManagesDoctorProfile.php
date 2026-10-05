<?php
namespace App\Http\Controllers\Concerns;

use App\Models\{City, Doctor, Speciality};
use Illuminate\Http\Request;

/** Shared doctor-profile form logic, used by the admin panel and by doctors editing their own profile. */
trait ManagesDoctorProfile
{
    /** $panel = 'admin' or 'doctor' - decides form action and back link in the view. */
    protected function formData(?Doctor $doctor, string $panel = 'admin'): array
    {
        return [
            'doctor' => $doctor,
            'panel' => $panel,
            'specialities' => Speciality::orderBy('name')->get(),
            'cities' => City::orderBy('name')->get(),
            'days' => Doctor::DAYS,
        ];
    }

    protected function rules(?Doctor $doctor): array
    {
        $uid = $doctor?->user_id;
        return [
            'name' => 'required|max:100',
            'email' => 'required|email|unique:users,email' . ($uid ? ",$uid" : ''),
            'password' => ($doctor ? 'nullable' : 'required') . '|min:6',
            'phone' => 'nullable|max:20', 'speciality_id' => 'required|exists:specialities,id',
            'city_id' => 'required|exists:cities,id', 'qualification' => 'nullable|max:150',
            'designation' => 'nullable|max:100', 'license_no' => 'nullable|max:60',
            'gender' => 'nullable|in:Male,Female,Other', 'blood_group' => 'nullable|max:5',
            'dob' => 'nullable|date|before:today', 'address' => 'nullable|max:255',
            'languages' => 'nullable|string|max:200',
            'experience_years' => 'required|integer|min:0|max:70', 'fees' => 'required|integer|min:0',
            'slot_minutes' => 'required|in:10,15,20,30,45,60',
            'appointment_type' => 'required|in:Clinic,Online,Both',
            'advance_days' => 'required|integer|min:1|max:365',
            'about' => 'nullable|string', 'photo' => ($doctor?->photo ? 'nullable' : 'required') . '|image|max:2048',
            'education' => 'nullable|array', 'awards' => 'nullable|array', 'certifications' => 'nullable|array',
            'availability' => 'nullable|array',
        ];
    }

    /** Turn the raw form arrays into clean JSON-friendly data. */
    protected function payload(Request $r, array $d, ?string $old = null): array
    {
        $rows = fn ($key, array $fields) => collect($d[$key] ?? [])
            ->map(fn ($row) => collect($fields)->mapWithKeys(fn ($f) => [$f => trim((string) ($row[$f] ?? ''))])->all())
            ->filter(fn ($row) => $row[$fields[0]] !== '')->values()->all();

        $availability = [];
        foreach (array_keys(Doctor::DAYS) as $day) {
            $availability[$day] = [];
            foreach (($d['availability'][$day] ?? []) as $win) {
                $from = $win['from'] ?? null; $to = $win['to'] ?? null;
                if ($from && $to && $to > $from) $availability[$day][] = [$from, $to];
            }
        }

        $data = collect($d)->only(['speciality_id', 'city_id', 'qualification', 'designation', 'license_no', 'gender', 'blood_group',
            'dob', 'address', 'experience_years', 'fees', 'slot_minutes', 'appointment_type', 'advance_days', 'about'])->all();
        $data['languages'] = collect(explode(',', $d['languages'] ?? ''))->map('trim')->filter()->values()->all();
        $data['education'] = $rows('education', ['school', 'degree', 'from', 'to']);
        $data['awards'] = $rows('awards', ['title', 'description']);
        $data['certifications'] = $rows('certifications', ['title', 'description']);
        $data['availability'] = $availability;

        if ($r->hasFile('photo')) {
            $file = $r->file('photo');
            $this->deletePhoto($old);
            $name = uniqid('doc_') . '.' . $file->extension();
            $file->move(public_path('uploads/doctors'), $name);
            $data['photo'] = 'uploads/doctors/' . $name;
        }
        return $data;
    }

    /** Remove an uploaded photo file from public/uploads/doctors (ignored if missing). */
    protected function deletePhoto(?string $path): void
    {
        if ($path && str_starts_with($path, 'uploads/doctors/') && is_file(public_path($path))) {
            @unlink(public_path($path));
        }
    }
}