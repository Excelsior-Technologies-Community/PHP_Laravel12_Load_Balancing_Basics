<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * LoadMetric Model
 * 
 * Represents performance metrics for server instances.
 * Tracks load balancing algorithm performance and statistics.
 */
class LoadMetric extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'server_instance_id',
        'algorithm',
        'total_requests',
        'avg_response_time',
        'peak_load',
        'peak_load_time',
        'success_count',
        'error_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'avg_response_time' => 'decimal:2',
        'peak_load_time' => 'datetime',
    ];

    /**
     * Get the server instance for these metrics.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function server()
    {
        return $this->belongsTo(ServerInstance::class, 'server_instance_id');
    }

    /**
     * Calculate the success rate percentage.
     *
     * @return float
     */
    public function getSuccessRateAttribute(): float
    {
        $total = $this->success_count + $this->error_count;
        return $total > 0 ? ($this->success_count / $total) * 100 : 0;
    }

    /**
     * Update peak load if current load is higher.
     *
     * @param int $currentLoad
     * @return void
     */
    public function updatePeakLoad(int $currentLoad): void
    {
        if ($currentLoad > $this->peak_load) {
            $this->update([
                'peak_load' => $currentLoad,
                'peak_load_time' => now(),
            ]);
        }
    }
}
