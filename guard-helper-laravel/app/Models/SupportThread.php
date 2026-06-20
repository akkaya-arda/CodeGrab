<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportThread extends Model
{
    use HasFactory;

    protected $fillable = [
        'access_grant_id',
        'token',
        'user_email',
        'platform',
        'status',
    ];

    protected $casts = [
        'access_grant_id' => 'integer',
    ];

    
    public function accessGrant()
    {
        return $this->belongsTo(AccessGrant::class);
    }

    
    public function messages()
    {
        return $this->hasMany(SupportMessage::class);
    }
}
