<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AccessGrant extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_bundle_id',
        'token',
        'email',
        'platform',
        'tag',
        'limit',
        'uses',
        'is_active',
        'expires_at',
        'hide_email',
    ];

    protected $casts = [
        'account_bundle_id' => 'integer',
        'tag' => 'string',
        'limit' => 'integer',
        'uses' => 'integer',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'hide_email' => 'boolean',
    ];

    
    public function accountBundle()
    {
        return $this->belongsTo(AccountBundle::class);
    }

    
    public static function generateToken(?string $prefix = null): string
    {
        $prefix = $prefix ? trim($prefix) : 'gh_tok_';
        return $prefix . Str::random(32);
    }
}
