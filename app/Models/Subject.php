<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = [
        'name',
        'code',
        'category',
        'credits',
        'is_elective',
    ];

    protected $casts = [
        'credits' => 'integer',
        'is_elective' => 'boolean',
    ];
}
