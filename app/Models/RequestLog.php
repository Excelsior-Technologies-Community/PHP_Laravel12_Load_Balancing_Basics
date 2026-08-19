<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * RequestLog Model
 * 
 * Represents a single request log entry for tracking
 * requests handled by server instances.
 */
class RequestLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'server_instance_id',
        'method',
        'path',
        'client_ip',
        'response_time',
        'status_code',
        'user_agent',
    ];

    /**
     * Disable timestamps as we use created_at only.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Get the server instance that handled this request.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function server()
    {
        return $this->belongsTo(ServerInstance::class, 'server_instance_id');
    }

    /**
     * Scope to filter successful requests.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status_code', '<', 400);
    }

    /**
     * Scope to filter failed requests.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('status_code', '>=', 400);
    }
}
