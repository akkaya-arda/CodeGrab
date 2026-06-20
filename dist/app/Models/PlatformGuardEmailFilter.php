<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformGuardEmailFilter extends Model
{
    protected $table = 'platforms';
    protected $fillable = [
        'name',
        'logo',
        'sender',
        'subject',
        'regex',
        'enable_heuristic',
        'grabbing_strategy',
    ];
    protected $casts = [
        'enable_heuristic' => 'boolean',
    ];
    public $timestamps = false;
}
