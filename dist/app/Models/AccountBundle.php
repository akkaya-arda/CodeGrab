<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;

class AccountBundle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'login_username',
        'platform',
        'password',
        'is_active',
        'hide_email'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'hide_email' => 'boolean',
    ];

    
    protected function password(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => Crypt::decryptString($value),
            set: fn (string $value) => Crypt::encryptString($value),
        );
    }

    
    public function accessGrants()
    {
        return $this->hasMany(AccessGrant::class);
    }
}
