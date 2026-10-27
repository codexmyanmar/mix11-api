<?php
// app/Models/ApiLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    protected $table = 'api_logs';
    
    protected $fillable = [
        'api_name',
        'method',
        'url',
        'request_payload',
        'response',
        'status',
        'error_message',
        'created_at',
        'updated_at'
    ];
    
    protected $casts = [
        'request_payload' => 'array',
        'response' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    /**
     * Scope for successful calls
     */
    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }
    
    /**
     * Scope for failed calls
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
    
    /**
     * Scope by API name
     */
    public function scopeByApi($query, $apiName)
    {
        return $query->where('api_name', $apiName);
    }
    
    /**
     * Scope by date range
     */
    public function scopeDateBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}