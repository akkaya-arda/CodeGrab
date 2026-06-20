<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class GmailAccount extends Model
{
    protected $fillable = [
        'id',
        'email',
        'access_token',
        'access_token_expires_at',
        'refresh_token',
        'refresh_token_expires_at',
        'is_active',
        'fetch_count',
        'last_used_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    public $timestamps = false;

    
    protected function accessToken(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                if (empty($value)) return $value;
                try {
                    return Crypt::decryptString($value);
                } catch (DecryptException $e) {
                    return $value;
                }
            },
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    
    protected function refreshToken(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                if (empty($value)) return $value;
                try {
                    return Crypt::decryptString($value);
                } catch (DecryptException $e) {
                    return $value;
                }
            },
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }
}
