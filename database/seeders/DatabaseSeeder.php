<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Database Seeder
 * 
 * Main seeder that calls all other seeders to populate
 * the database with demo data.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * 
     * @return void
     */
    public function run(): void
    {
        $this->call([
            ServerInstanceSeeder::class,
            LoadMetricSeeder::class,
        ]);
    }
}
