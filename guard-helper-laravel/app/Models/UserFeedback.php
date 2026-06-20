<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserFeedback extends Model
{
    protected $table = 'user_feedbacks';

    protected $fillable = [
        'email',
        'platform',
        'is_working',
        'comment',
        'log_id'
    ];

    protected $casts = [
        'is_working' => 'boolean',
    ];

    public function fetchLog()
    {
        return $this->belongsTo(GuardFetchLog::class, 'log_id');
    }
}
