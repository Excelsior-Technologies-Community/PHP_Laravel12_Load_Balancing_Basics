<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerPerformanceHistory extends Model
{
    protected $fillable = [
        'server_instance_id',
        'request_count',
        'active_connections',
        'avg_response_time',
        'success_count',
        'error_count',
        'recorded_at',
    ];

    protected $casts = [
        'avg_response_time' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(
            ServerInstance::class,
            'server_instance_id'
        );
    }

    public function getSuccessRateAttribute(): float
    {
        $total = $this->success_count + $this->error_count;

        if ($total === 0) {
            return 0;
        }

        return round(
            ($this->success_count / $total) * 100,
            2
        );
    }
}
