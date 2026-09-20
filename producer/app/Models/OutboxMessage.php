<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutboxMessage extends Model
{
    protected $fillable = [
        'id',
        'event_type',
        'exchange',
        'routing_key',
        'payload',
        'headers',
        'occurred_at',
        'available_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'headers' => 'array',
        'occurred_at' => 'datetime',
        'available_at' => 'datetime',
        'published_at' => 'datetime',
    ];
    
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
}
