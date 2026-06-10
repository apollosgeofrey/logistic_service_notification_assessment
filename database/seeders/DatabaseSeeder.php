<?php

namespace Database\Seeders;

use App\Models\Subscriber;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Subscriber::query()->updateOrCreate(
            ['email' => 'alice@example.com'],
            ['name' => 'Alice Email', 'phone' => null]
        );

        Subscriber::query()->updateOrCreate(
            ['phone' => '+15551234567'],
            ['name' => 'Bob SMS', 'email' => null]
        );

        Subscriber::query()->updateOrCreate(
            ['email' => 'carol@example.com'],
            ['name' => 'Carol Both', 'phone' => '+15559876543']
        );
    }
}
