<?php

namespace Database\Seeders;

use App\Models\Item;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use App\Models\Collection;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(10)->create();

        User::factory()->create([
            'name' => 'SleepyWear',
            'email' => 'lovelypintes@gmail.com',
            'password' => bcrypt('password'),
        ]);

        Collection::factory()
            ->count(5)
            ->create()
            ->each(function ($collection) {
                Item::factory()
                    ->count(10)
                    ->for($collection) // link items to this collection
                    ->create();
            });
    }
}
