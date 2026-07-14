<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::query()->firstOrCreate(
            ['email' => 'admin@ezjualan.test'],
            [
                'name' => 'Admin EzJualan',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $this->call(ProductSeeder::class);
    }
}
