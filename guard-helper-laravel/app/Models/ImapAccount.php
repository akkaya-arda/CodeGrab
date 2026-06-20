<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;

class ImapAccount extends Model
{
    protected $fillable = [
        'email',
        'host',
        'port',
        'encryption',
        'password',
        'is_active',
        'fetch_count',
        'last_used_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    
    protected function password(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => Crypt::decryptString($value),
            set: fn (string $value) => Crypt::encryptString($value),
        );
    }
}
