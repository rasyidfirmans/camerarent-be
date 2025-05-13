<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'name' => 'Robby Cantik',
            'email' => fake()->email(),
            'username' => 'RobbyCantik',
            'password' => hash('sha256', 'admincantik256'),
            'phone_number' => fake()->phoneNumber(),
            'citizenship_image' => fake()->image(),
            'is_admin' => true,
        ]);
    }
}
