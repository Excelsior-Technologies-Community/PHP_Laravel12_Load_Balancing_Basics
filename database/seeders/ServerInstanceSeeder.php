<?php

namespace Database\Seeders;

use App\Models\LoadMetric;
use App\Models\RequestLog;
use App\Models\ServerInstance;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * ServerInstance Seeder
 * 
 * Seeds the database with demo server instances for the
 * load balancing system demonstration.
 */
class ServerInstanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates demo server instances with different weights and statuses
     * to demonstrate load balancing algorithms.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        LoadMetric::truncate();
        RequestLog::truncate();
        ServerInstance::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Demo server instances with different weights for load balancing
        $servers = [
            [
                'name' => 'Server-1',
                'ip_address' => '127.0.0.1',
                'port' => 8001,
                'hostname' => 'localhost',
                'weight' => 3,
                'is_online' => true,
                'algorithm' => 'round_robin',
            ],
            [
                'name' => 'Server-2',
                'ip_address' => '127.0.0.1',
                'port' => 8002,
                'hostname' => 'localhost',
                'weight' => 2,
                'is_online' => true,
                'algorithm' => 'round_robin',
            ],
            [
                'name' => 'Server-3',
                'ip_address' => '127.0.0.1',
                'port' => 8003,
                'hostname' => 'localhost',
                'weight' => 1,
                'is_online' => true,
                'algorithm' => 'round_robin',
            ],
            [
                'name' => 'Server-4',
                'ip_address' => '127.0.0.1',
                'port' => 8004,
                'hostname' => 'localhost',
                'weight' => 2,
                'is_online' => false,
                'algorithm' => 'round_robin',
            ],
        ];

        foreach ($servers as $serverData) {
            ServerInstance::create([
                'name' => $serverData['name'],
                'ip_address' => $serverData['ip_address'],
                'port' => $serverData['port'],
                'hostname' => $serverData['hostname'],
                'weight' => $serverData['weight'],
                'is_online' => $serverData['is_online'],
                'algorithm' => $serverData['algorithm'],
                'request_count' => 0,
                'active_connections' => 0,
                'last_heartbeat' => now(),
                'metadata' => [
                    'description' => 'Demo server instance for load balancing',
                    'region' => 'local',
                ],
            ]);
        }
    }
}
