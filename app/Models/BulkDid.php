<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToUser;

class BulkDid extends Model
{
    use BelongsToUser;

    protected $table = 'bulk_dids';

    protected $fillable = [
        'phone_number',
        'source_ip',
        'status',
        'last_tested_at',
    ];

    protected $casts = [
        'last_tested_at' => 'datetime',
    ];
}
