<?php

namespace Database\Seeders;

use App\Models\LoadMetric;
use App\Models\ServerInstance;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * LoadMetric Seeder
 * 
 * Seeds the database with demo load metrics for server instances.
 */
class LoadMetricSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates demo load metrics for each server instance
     * to demonstrate performance monitoring.
     */
    public function run(): void
    {
        $servers = ServerInstance::all();

        foreach ($servers as $server) {
            LoadMetric::create([
                'server_instance_id' => $server->id,
                'algorithm' => 'round_robin',
                'total_requests' => rand(100, 1000),
                'avg_response_time' => rand(50, 200) / 100,
                'peak_load' => rand(10, 50),
                'peak_load_time' => now()->subHours(rand(1, 24)),
                'success_count' => rand(90, 100),
                'error_count' => rand(0, 10),
            ]);
        }
    }
}
