<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cdr extends Model
{
    protected $table = 'cdr';

    protected $fillable = [
        'caller_id',
        'destination',
        'duration',
        'billsec',
        'disposition',
        'start_time',
    ];

    protected $casts = [
        'duration' => 'integer',
        'billsec' => 'integer',
        'start_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
