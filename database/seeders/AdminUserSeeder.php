<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'info@texa.ng'],
            [
                'name' => 'texa',
                'password' => Hash::make('Ayuk.texa1'),
                'is_admin' => true,
            ]
        );
    }
}
