<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuardFetchLog extends Model
{
    protected $table = 'guard_fetch_logs';

    protected $fillable = [
        'email',
        'account_type',
        'platform',
        'status',
        'code',
        'error_message',
        'grab_pattern'
    ];
}
