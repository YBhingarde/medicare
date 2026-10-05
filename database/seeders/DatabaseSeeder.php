<?php
namespace Database\Seeders;

use App\Models\{City, Doctor, Speciality, User};
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create(['name' => 'Admin', 'email' => 'admin@medicare.test', 'password' => 'password', 'role' => 'admin', 'email_verified_at' => now()]);

        foreach (['Cardiologist', 'Dermatologist', 'Pediatrician', 'Gynecologist', 'Orthopedic', 'Dentist', 'ENT', 'General Physician'] as $n) Speciality::create(['name' => $n]);
        foreach (['Mumbai', 'Pune', 'Nagpur', 'Nashik', 'Delhi', 'Bengaluru'] as $n) City::create(['name' => $n]);

        $docs = [
            ['Dr. Anil Kulkarni', 1, 1, 15, 800, 'MBBS, MD (Cardiology)', 'Senior Consultant Cardiologist', 'Male', 'O+'],
            ['Dr. Sneha Patil', 2, 2, 8, 500, 'MBBS, MD (Dermatology)', 'Consultant Dermatologist', 'Female', 'A+'],
            ['Dr. Rahul Deshmukh', 3, 2, 11, 600, 'MBBS, DCH', 'Child Specialist', 'Male', 'B+'],
            ['Dr. Meera Joshi', 4, 1, 13, 700, 'MBBS, MS (OBG)', 'Consultant Gynecologist', 'Female', 'AB+'],
            ['Dr. Vikram Shinde', 5, 3, 9, 650, 'MBBS, MS (Ortho)', 'Orthopedic Surgeon', 'Male', 'O-'],
            ['Dr. Pooja More', 6, 4, 6, 400, 'BDS, MDS', 'Dental Surgeon', 'Female', 'A-'],
        ];
        $week = fn ($morning, $evening) => [
            'mon' => [$morning, $evening], 'tue' => [$morning, $evening], 'wed' => [$morning], 'thu' => [$morning, $evening],
            'fri' => [$morning, $evening], 'sat' => [$morning], 'sun' => [],
        ];
        foreach ($docs as $i => [$name, $sp, $city, $exp, $fees, $qual, $desig, $gender, $blood]) {
            $u = User::create(['name' => $name, 'email' => 'doctor' . ($i + 1) . '@medicare.test', 'phone' => '+91 98200 0' . (1000 + $i), 'password' => 'password', 'role' => 'doctor', 'email_verified_at' => now()]);
            Doctor::create([
                'user_id' => $u->id, 'speciality_id' => $sp, 'city_id' => $city, 'experience_years' => $exp,
                'fees' => $fees, 'qualification' => $qual, 'approved' => true, 'designation' => $desig,
                'license_no' => 'MMC-' . (200100 + $i * 37), 'gender' => $gender, 'blood_group' => $blood,
                'dob' => now()->subYears(28 + $exp)->startOfYear()->addDays($i * 41),
                'address' => 'Shop ' . (10 + $i) . ', Medical Plaza, MG Road', 'appointment_type' => $i % 3 === 2 ? 'Both' : 'Clinic',
                'advance_days' => 30, 'slot_minutes' => 20,
                'languages' => ['English', 'Hindi', 'Marathi'],
                'about' => "$name has $exp years of experience and sees patients by appointment. Known for clear explanations, careful diagnosis and a calm, patient-first approach to long-term care.",
                'education' => [
                    ['school' => 'Grant Medical College, Mumbai', 'degree' => explode(',', $qual)[0], 'from' => (string) (now()->year - $exp - 9), 'to' => (string) (now()->year - $exp - 4)],
                    ['school' => 'Seth GS Medical College, Mumbai', 'degree' => trim(explode(',', $qual)[1] ?? 'Residency'), 'from' => (string) (now()->year - $exp - 4), 'to' => (string) (now()->year - $exp)],
                ],
                'awards' => [
                    ['title' => 'Best Doctor Award (' . (now()->year - 2) . ')', 'description' => 'Recognised for outstanding patient outcomes and care.'],
                    ['title' => 'Patient Choice Award (' . (now()->year - 3) . ')', 'description' => 'Awarded for consistently high patient satisfaction ratings.'],
                ],
                'certifications' => [
                    ['title' => 'Basic Life Support (BLS), ' . (now()->year - 1), 'description' => 'Certified in life-saving techniques including CPR.'],
                ],
                'availability' => $week(['10:00', '13:00'], ['17:00', '20:00']),
            ]);
        }
    }
}
