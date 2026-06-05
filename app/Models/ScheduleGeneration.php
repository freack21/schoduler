<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleGeneration extends Model
{
    protected $fillable = [
        'status',
        'generation',
        'fitness',
        'violations',
        'dist_violations',
        'message',
        'max_generations',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
