<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Order;
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
            'email' => 'lovelypintes@gmail.com',
            'password' => bcrypt('password'),
        ]);

        // ─── Additional accounts ──────────────────────────────────
        $users = [
            ['name' => 'ToyotaZ', 'email' => 'ruthmayreginos2786@gmail.com'],
            ['name' => 'SamsungZ', 'email' => 'sofiaisabellatina@gmail.com'],
            ['name' => 'GoogleZ', 'email' => 'myriahvielle619@gmail.com'],
            ['name' => 'Sleepywears1', 'email' => 'angelesalyannamarie@gmail.com'],
            ['name' => 'Sleepywears', 'email' => 'sleepywears.ph1@gmail.com'],

            // ─── Instructors accounts ────────────────────────────────
            ['name' => 'Elmer', 'email' => 'elmeralvarado@laverdad.edu.ph'],
            ['name' => 'Giselle', 'email' => 'magiselledionisio@laverdad.edu.ph'],
            ['name' => 'Gian', 'email' => 'giancarlo.gallon@laverdad.edu.ph'],
        ];

        foreach ($users as $user) {
            User::create([
                'name' => $user['name'],
                'email' => $user['email'],
                'password' => bcrypt('P4$$w0rD!_Secure'),
            ]);
        }

        // $userId = 1;
        // ─── Seed 50 Collections ─────────────────────────────────
        // for ($i = 1; $i <= 50; $i++) {
        //     $collection = Collection::create([
        //         'user_id' => $userId,
        //         'name' => $this->ordinal($i) . ' Collection',
        //         'release_date' => Carbon::now()->subDays(rand(0, 365)),
        //         'qty' => 70,
        //         'stock_qty' => 70,
        //         'capital' => rand(5000, 20000),
        //         'total_sales' => 0,
        //         'status' => 'Active',
        //     ]);

        // ─── Seed 120 Items per Collection ──────────────────────
        //     $itemsData = [];
        //     for ($j = 1; $j <= 120; $j++) {
        //         $itemCode = sprintf('%03d%03d', $i, $j);

        //         $itemsData[] = [
        //             'user_id' => $userId,
        //             'collection_id' => $collection->id,
        //             'code' => $itemCode,
        //             'name' => "Item {$j} of {$collection->name}",
        //             'price' => rand(100, 999),
        //             'image' => "https://via.placeholder.com/200x200.png?text=Item+{$j}",
        //             'status' => 'Available',
        //             'created_at' => now(),
        //             'updated_at' => now(),
        //         ];
        //     }

        //     Item::insert($itemsData);
        // }

        // ─── Seed 50 Customers ───────────────────────────────────
        // $customers = [];
        // for ($i = 1; $i <= 50; $i++) {
        //     $customers[] = Customer::create([
        //         'user_id' => $userId,
        //         'first_name' => "CustomerFirst{$i}",
        //         'last_name' => "CustomerLast{$i}",
        //         'address' => "Address {$i}, City, Country",
        //         'contact_number' => '0917' . rand(1000000, 9999999),
        //         'social_handle' => "@customer{$i}",
        //     ]);
        // }

        // ─── Seed 50 Orders ──────────────────────────────────────
        // foreach ($customers as $customer) {
        //     Order::create([
        //         'user_id' => $userId,
        //         'customer_id' => $customer->id,
        //         'first_name' => $customer->first_name,
        //         'last_name' => $customer->last_name,
        //         'address' => $customer->address,
        //         'contact_number' => $customer->contact_number,
        //         'social_handle' => $customer->social_handle,
        //         'order_date' => Carbon::now()->subDays(rand(0, 30)),
        //         'total' => rand(500, 5000),
        //     ]);
        // }
    }

    /**
     * Convert a number to its ordinal representation (e.g. 1 -> 1st)
     */
    // private function ordinal($number)
    // {
    //     $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
    //     if (($number % 100) >= 11 && ($number % 100) <= 13) {
    //         return $number . 'th';
    //     } else {
    //         return $number . $ends[$number % 10];
    //     }
    // }
}
