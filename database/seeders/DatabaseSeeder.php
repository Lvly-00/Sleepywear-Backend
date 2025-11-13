<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\Item;
use App\Models\User;
use App\Models\Customer;
use App\Models\Order;
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

        // ─── Seed 50 Collections ─────────────────────────────────
        for ($i = 1; $i <= 50; $i++) {
            $collection = Collection::create([
                'name' => $this->ordinal($i) . ' Collection',
                'release_date' => Carbon::now()->subDays(rand(0, 365)),
                'qty' => 70,
                'stock_qty' => 70,
                'capital' => rand(5000, 20000),
                'total_sales' => 0,
                'status' => 'Active',
            ]);

            // ─── Seed 120 Items per Collection ──────────────────────
            $itemsData = [];
            for ($j = 1; $j <= 120; $j++) {
                $itemCode = sprintf('%03d%03d', $i, $j);

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

        // ─── Seed 50 Customers ───────────────────────────────────
        $customers = [];
        for ($i = 1; $i <= 50; $i++) {
            $customers[] = Customer::create([
                'first_name' => "CustomerFirst{$i}",
                'last_name' => "CustomerLast{$i}",
                'address' => "Address {$i}, City, Country",
                'contact_number' => '0917' . rand(1000000, 9999999),
                'social_handle' => "@customer{$i}",
            ]);
        }

        // ─── Seed 50 Orders ──────────────────────────────────────
        foreach ($customers as $customer) {
            Order::create([
                'customer_id' => $customer->id,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'address' => $customer->address,
                'contact_number' => $customer->contact_number,
                'social_handle' => $customer->social_handle,
                'order_date' => Carbon::now()->subDays(rand(0, 30)),
                'total' => rand(500, 5000),
            ]);
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
