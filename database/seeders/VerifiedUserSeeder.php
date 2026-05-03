<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class VerifiedUserSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            User::updateOrCreate(
                ['email' => 'rodolph.khoury@net.usj.edu.lb', 'card_id' => 'FD:6B:13:05'],
                [
                    'name' => 'Rodolph Khoury',
                    'password' => Hash::make('rodolph1234'),
                    'email_verified_at' => Carbon::now(),
                ]
            );
        });
    }
}
