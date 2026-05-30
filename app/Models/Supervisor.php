<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supervisor extends Model
{
    /** @use HasFactory<\Database\Factories\SupervisorFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'national_id',
        'birth_date',
        'birth_place',
        'nationality',
        'gender',
        'academic_rank',
        'qualification',
        'institution',
        'email',
        'phone',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function thesisRegistrations(): HasMany
    {
        return $this->hasMany(ThesisRegistration::class);
    }
}
