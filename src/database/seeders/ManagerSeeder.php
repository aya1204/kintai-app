<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Manager;
use Illuminate\Support\Facades\Hash;

class ManagerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        Manager::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'password' => Hash::make('password')
            ],
        );
    }
}
