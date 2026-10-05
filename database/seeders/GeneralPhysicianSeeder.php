<?php
namespace Database\Seeders;

use App\Models\{City, Doctor, Speciality, User};
use Illuminate\Database\Seeder;

/** Adds one General Physician to an existing database (safe to run twice).
 *  php artisan db:seed --class=GeneralPhysicianSeeder */
class GeneralPhysicianSeeder extends Seeder
{
    public function run(): void
    {
        $sp = Speciality::firstOrCreate(['name' => 'General Physician']);
        $city = City::firstOrCreate(['name' => 'Mumbai']);
        $email = 'doctor7@medicare.test';
        if (User::where('email', $email)->exists()) return;

        $u = User::create(['name' => 'Dr. Amit Kale', 'email' => $email, 'phone' => '+91 98200 01006', 'password' => 'password', 'role' => 'doctor', 'email_verified_at' => now()]);
        $morning = ['10:00', '13:00']; $evening = ['17:00', '20:00'];
        Doctor::create([
            'user_id' => $u->id, 'speciality_id' => $sp->id, 'city_id' => $city->id, 'experience_years' => 10, 'fees' => 300,
            'qualification' => 'MBBS, MD (Medicine)', 'approved' => true, 'designation' => 'General Physician',
            'license_no' => 'MMC-200420', 'gender' => 'Male', 'blood_group' => 'B+',
            'dob' => now()->subYears(38)->startOfYear(), 'address' => 'Shop 16, Medical Plaza, MG Road', 'appointment_type' => 'Both',
            'advance_days' => 30, 'slot_minutes' => 20, 'languages' => ['English', 'Hindi', 'Marathi'],
            'about' => 'Dr. Amit Kale is a General Physician with 10 years of experience in fever, infections, diabetes, blood pressure and everyday health concerns.',
            'availability' => ['mon' => [$morning, $evening], 'tue' => [$morning, $evening], 'wed' => [$morning], 'thu' => [$morning, $evening], 'fri' => [$morning, $evening], 'sat' => [$morning], 'sun' => []],
        ]);
    }
}
