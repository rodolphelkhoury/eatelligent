<?php

namespace Database\Seeders;

use App\Models\CafeteriaStaff;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CafeteriaStaffSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CafeteriaStaff::updateOrCreate([
            'email' => 'cafeteriastaff@eatelligent.com',
        ], [
            'name' => 'Cafeteria Staff',
            'password' => bcrypt('cafeteriastaff'),
        ]);
    }
}
