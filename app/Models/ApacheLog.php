<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApacheLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'ip_address',
        'request_time',
        'request_method',
        'request_path',
        'status_code',
        'response_size',
        'user_agent'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'request_time' => 'datetime',
        'response_size' => 'integer',
    ];
} 