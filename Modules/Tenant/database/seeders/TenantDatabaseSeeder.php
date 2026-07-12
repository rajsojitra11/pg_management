<?php

namespace Modules\Tenant\Database\Seeders;

use App\Traits\SeederLogging;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Role\Models\Role;
use Modules\Room\Models\Room;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Modules\User\Models\UserProfile;

class TenantDatabaseSeeder extends Seeder
{
    use SeederLogging;

    public function run(): void
    {
        $defaultDate = getDefaultMigrationDate();

        $superAdmin = User::where('username', 'super_admin')->first();
        $createdBy = $superAdmin?->id ?? 1;

        $country = DB::table('countries')->where('code', 'IN')->first();
        $state = DB::table('states')->where('code', 'GJ')->where('country_id', $country?->id)->first();
        $city = DB::table('cities')->where('name', 'Rajkot')->where('country_id', $country?->id)->where('state_id', $state?->id)->first();

        $rooms = Room::with('category', 'pg')->get();

        if ($rooms->isEmpty()) {
            $this->command->warn('No rooms found. Skipping Tenant seeding.');

            return;
        }

        $firstNames = [
            'Aarav', 'Vivaan', 'Aditya', 'Vihaan', 'Arjun', 'Sai', 'Reyansh', 'Ayaan',
            'Ishaan', 'Dhruv', 'Rudra', 'Shaurya', 'Yash', 'Sarthak', 'Dev', 'Ansh',
            'Rohan', 'Krishna', 'Manav', 'Harsh', 'Priya', 'Ananya', 'Diya', 'Isha',
            'Kavya', 'Nisha', 'Riya', 'Sara', 'Tanya', 'Uma', 'Vani', 'Zara',
        ];

        $lastNames = [
            'Sharma', 'Patel', 'Singh', 'Verma', 'Gupta', 'Reddy', 'Kumar', 'Joshi',
            'Desai', 'Mehta', 'Shah', 'Thakur', 'Mishra', 'Chauhan', 'Pandey', 'Rao',
            'Nair', 'Menon', 'Iyer', 'Das', 'Sen', 'Bose', 'Ghosh', 'Malhotra',
        ];

        $occupations = [
            'Software Engineer', 'Data Analyst', 'Graphic Designer', 'Content Writer',
            'Student', 'Teacher', 'Doctor', 'CA', 'Business Analyst', 'Marketing Manager',
        ];

        $genders = ['male', 'female', 'male', 'male', 'female'];
        $idProofTypes = ['Aadhar', 'PAN', 'Voter ID', 'Driving License', 'Passport'];

        $tenantIndex = 0;

        foreach ($rooms as $room) {
            $catName = $room->category?->category_name ?? 'General';
            $rentPerBed = match (true) {
                str_contains($catName, 'Two') => 2500,
                str_contains($catName, 'Three') => 1500,
                str_contains($catName, 'Four') => 1000,
                default => 2000,
            };

            $pgOwnerId = $room->pg?->owner_id ?? $createdBy;

            for ($i = 0; $i < 2; $i++) {
                $tenantIndex++;
                $firstName = $firstNames[array_rand($firstNames)];
                $lastName = $lastNames[array_rand($lastNames)];
                $fullName = "{$firstName} {$lastName}";
                $gender = $genders[array_rand($genders)];
                $occupation = $occupations[array_rand($occupations)];
                $idProofType = $idProofTypes[array_rand($idProofTypes)];
                $idProofNum = str_pad((string) random_int(1000, 9999), 12, '0', STR_PAD_BOTH);

                $username = "tenant_{$tenantIndex}";
                $email = "tenant_{$tenantIndex}@pgapp.com";

                $existingUser = User::where('username', $username)->first();
                if (! $existingUser) {
                    $user = User::create([
                        'name' => $fullName,
                        'mobile' => (string) random_int(7000000000, 9999999999),
                        'username' => $username,
                        'email' => $email,
                        'password' => bcrypt('Tenant@123'),
                        'status' => 'Active',
                        'created_by' => $pgOwnerId,
                        'updated_by' => $pgOwnerId,
                        'created_at' => $defaultDate,
                        'updated_at' => $defaultDate,
                    ]);

                    UserProfile::create([
                        'user_id' => $user->id,
                        'firstname' => $firstName,
                        'lastname' => $lastName,
                        'date_of_birth' => Carbon::parse($defaultDate)->subYears(random_int(18, 40))->format('Y-m-d'),
                        'gender' => $gender,
                        'state_id' => $state?->id,
                        'city_id' => $city?->id,
                        'parent_id' => $pgOwnerId,
                        'created_by' => $createdBy,
                        'updated_by' => $createdBy,
                        'created_at' => $defaultDate,
                        'updated_at' => $defaultDate,
                    ]);
                } else {
                    $user = $existingUser;
                }

                $tenantRole = Role::where('name', 'Tenant')->first();
                if ($tenantRole && ! $user->hasRole('Tenant')) {
                    $user->assignRole($tenantRole);
                }

                Tenant::create([
                    'user_id' => $user->id,
                    'name' => $fullName,
                    'email' => $email,
                    'phone' => $user->mobile,
                    'address' => "Room {$room->room_no}, {$room->pg?->pg_name}, Rajkot",
                    'status' => 'active',
                    'pg_id' => $room->pg_id,
                    'room_id' => $room->id,
                    'bed_no' => chr(65 + $i),
                    'date_of_birth' => Carbon::parse($defaultDate)->subYears(random_int(18, 40))->format('Y-m-d'),
                    'gender' => $gender,
                    'occupation' => $occupation,
                    'checkin_date' => $defaultDate,
                    'expected_checkout_date' => Carbon::parse($defaultDate)->addYear(),
                    'monthly_rent' => $rentPerBed,
                    'security_deposit' => $rentPerBed * 2,
                    'payment_method' => 'UPI',
                    'id_proof_type' => $idProofType,
                    'id_proof_number' => $idProofNum,
                    'emergency_contact_name' => 'Emergency Contact',
                    'emergency_relation' => 'Parent',
                    'emergency_contact_number' => (string) random_int(7000000000, 9999999999),
                    'permanent_state_id' => $state?->id,
                    'permanent_city_id' => $city?->id,
                    'permanent_address' => 'Permanent Address, City',
                    'additional_notes' => "Tenant in {$catName} room {$room->room_no}",
                    'created_by' => $createdBy,
                    'updated_by' => $createdBy,
                    'created_at' => $defaultDate,
                    'updated_at' => $defaultDate,
                ]);
            }
        }
    }
}
