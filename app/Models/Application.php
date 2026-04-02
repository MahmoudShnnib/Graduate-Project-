<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    protected $fillable = [
        'application_number',
        'full_name',
        'email',
        'phone',
        'specialization_id',
        'semester_type',
        'status',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'date',
    ];

    public function specialization()
    {
        return $this->belongsTo(Specialization::class);
    }
}
