<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PPCallLog extends Model
{
    use HasFactory;

    protected $table = 'pp_call_logs';
    
    public $timestamps = false;
    
    protected $fillable = [
        'direction',
        'method',
        'url',
        'request_headers',
        'request_body',
        'response_body',
        'response_status',
        'user_id',
        'ip_address',
        'execution_time_ms'
    ];

    protected $casts = [
        'request_headers' => 'array',
        'request_body' => 'array',
        'response_body' => 'array',
        'created_at' => 'datetime'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeIncoming($query)
    {
        return $query->where('direction', 'incoming');
    }

    public function scopeOutgoing($query)
    {
        return $query->where('direction', 'outgoing');
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('method', $method);
    }

    public function scopeFailed($query)
    {
        return $query->where('response_status', '>=', 400);
    }
}