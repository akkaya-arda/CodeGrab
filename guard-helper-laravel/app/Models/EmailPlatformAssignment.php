<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailPlatformAssignment extends Model
{
    protected $table = 'email_platform_assignments';

    protected $fillable = [
        'email',
        'platform_id',
    ];

    public $timestamps = false;

    public function platform()
    {
        return $this->belongsTo(PlatformGuardEmailFilter::class, 'platform_id');
    }
}
