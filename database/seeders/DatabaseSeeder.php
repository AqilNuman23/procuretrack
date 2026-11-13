<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Foundation numbering uses model events; do not disable them here.
        $this->call(FoundationSeeder::class);
    }
}
