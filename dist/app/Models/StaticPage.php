<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaticPage extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'content',
        'is_published',
        'show_in_footer'
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'show_in_footer' => 'boolean',
    ];
}
