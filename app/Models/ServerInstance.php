<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerInstance extends Model
{
    protected $fillable = [
        'name',
        'ip_address',
        'port',
        'hostname',
        'is_online',
        'request_count',
        'active_connections',
        'weight',
        'last_heartbeat',
        'algorithm',
        'metadata',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'last_heartbeat' => 'datetime',
        'metadata' => 'array',
    ];

    public function requestLogs()
    {
        return $this->hasMany(RequestLog::class);
    }

    public function loadMetrics()
    {
        return $this->hasMany(LoadMetric::class);
    }

    public function performanceHistories()
    {
        return $this->hasMany(
            ServerPerformanceHistory::class,
            'server_instance_id'
        );
    }

    public function scopeOnline($query)
    {
        return $query->where('is_online', true);
    }

    public function updateHeartbeat(): void
    {
        $this->update([
            'last_heartbeat' => now()
        ]);
    }

    public function incrementRequestCount(): void
    {
        $this->increment('request_count');
    }

    public function isHealthy(): bool
    {
        return $this->is_online &&
            $this->last_heartbeat &&
            $this->last_heartbeat->gt(
                now()->subSeconds(30)
            );
    }
}
