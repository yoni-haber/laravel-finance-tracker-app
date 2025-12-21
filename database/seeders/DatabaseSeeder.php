<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Run all seeders inside a transaction so a failure rolls everything back.
        DB::transaction(function () {
            $this->call([
                UserAndFinanceSeeder::class,
                NetWorthSeeder::class,
                SupportTablesSeeder::class,
            ]);
        });
    }
}
