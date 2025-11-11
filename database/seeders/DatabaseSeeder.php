<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Main business account ────────────────────────────────
        User::create([
            'name' => 'SleepyWears',
            'business_name' => 'SleepyWear Company',
            'email' => 'lovelypintes@gmail.com',
            'password' => bcrypt('password'),
        ]);

        // ─── Additional accounts ──────────────────────────────────
        $users = [
            ['name' => 'ToyotaZ', 'business_name' => 'Toyota', 'email' => 'ruthmayreginos2786@gmail.com'],
            ['name' => 'SamsungZ', 'business_name' => 'Samsung', 'email' => 'sofiaisabellatina@gmail.com'],
            ['name' => 'AppleZZ', 'business_name' => 'Apple', 'email' => 'kirkbondoc31@gmail.com'],
            ['name' => 'GoogleZ', 'business_name' => 'Google', 'email' => 'myriahvielle619@gmail.com'],
        ];

        foreach ($users as $user) {
            User::create([
                'name' => $user['name'],
                'business_name' => $user['business_name'],
                'email' => $user['email'],
                'password' => bcrypt('password'),
            ]);
        }

        // ─── Seed 100 Collections ─────────────────────────────────
        for ($i = 1; $i <= 1; $i++) {
            $collection = Collection::create([
                'name' => $this->ordinal($i) . ' Collection', // e.g. 1st Collection
                'release_date' => Carbon::now()->subDays(rand(0, 365)),
                'qty' => 70,
                'stock_qty' => 70,
                'capital' => rand(5000, 20000),
                'total_sales' => 0,
                'status' => 'Active',
            ]);

        // ─── Seed 70 Items per Collection ──────────────────────
                $itemsData = [];
                for ($j = 1; $j <= 20; $j++) {
                    $itemCode = sprintf('%d%02d', $i, $j); // e.g. 101, 102, 103...

                    $itemsData[] = [
                        'collection_id' => $collection->id,
                        'code' => $itemCode,
                        'name' => "Item {$j} of {$collection->name}",
                        'price' => rand(100, 999),
                        'image' => "https://via.placeholder.com/200x200.png?text=Item+{$j}",
                        'status' => 'Available',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                Item::insert($itemsData);
            }
        }

        /**
         * Convert a number to its ordinal representation (e.g. 1 -> 1st)
         */
        private function ordinal($number)
        {
            $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
            if (($number % 100) >= 11 && ($number % 100) <= 13) {
                return $number . 'th';
            } else {
                return $number . $ends[$number % 10];
            }
    }
}
