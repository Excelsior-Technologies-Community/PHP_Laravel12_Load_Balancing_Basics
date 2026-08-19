<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ServerInstance Model
 * 
 * Represents a server instance in the load balancing system.
 * Tracks server status, connections, and performance metrics.
 */
class ServerInstance extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_online' => 'boolean',
        'last_heartbeat' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get all request logs for this server instance.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function requestLogs()
    {
        return $this->hasMany(RequestLog::class);
    }

    /**
     * Get all load metrics for this server instance.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function loadMetrics()
    {
        return $this->hasMany(LoadMetric::class);
    }

    /**
     * Scope to filter only online servers.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOnline($query)
    {
        return $query->where('is_online', true);
    }

    /**
     * Update the heartbeat timestamp for this server.
     *
     * @return void
     */
    public function updateHeartbeat(): void
    {
        $this->update(['last_heartbeat' => now()]);
    }

    /**
     * Increment the request count for this server.
     *
     * @return void
     */
    public function incrementRequestCount(): void
    {
        $this->increment('request_count');
    }

    /**
     * Check if the server is healthy based on heartbeat.
     *
     * @return bool
     */
    public function isHealthy(): bool
    {
        return $this->is_online && 
               $this->last_heartbeat && 
               $this->last_heartbeat->gt(now()->subSeconds(30));
    }
}
