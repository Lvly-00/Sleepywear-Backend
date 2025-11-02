<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Main business account
        User::create([
            'name' => 'SleepyWears',
            'business_name' => 'SleepyWear Company',
            'email' => 'lovelypintes@gmail.com',
            'password' => bcrypt('password'),
        ]);

        // Additional accounts
        $users = [
            ['name' => 'ToyotaZ', 'business_name' => 'Toyota', 'email' => 'ruthmayreginos2786@gmail.com'],
            ['name' => 'SamsungZ', 'business_name' => 'Samsung', 'email' => 'sofiaisabellatina@gmail.com'],
            ['name' => 'AppleZZ', 'business_name' => 'Apple', 'email' => 'kirkbondoc31@gmail.com'],
            ['name' => 'GoogleZ', 'business_name' => 'Google', 'email' => 'myriahvielle619@gmail.com'], // fixed business_name here
        ];

        foreach ($users as $user) {
            User::create([
                'name' => $user['name'],
                'business_name' => $user['business_name'],
                'email' => $user['email'],
                'password' => bcrypt('password'),
            ]);
        }

        // Seed collections and items per collection
        Collection::factory()
            ->count(30)
            ->create()
            ->each(function ($collection) {
                Item::factory()
                    ->count(70)
                    ->create([
                        'collection_id' => $collection->id,
                    ]);
            });
    }
}
