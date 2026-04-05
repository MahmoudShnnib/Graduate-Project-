<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'last_name',
        'mother_name',
        'birth_date',
        'birth_place',
        'gender',
        'marital_status',
        'nationality',
        'national_id',
        'card_number',
        'residence',
        'profession',
        'workplace',
        'permanent_address',
        'study_type',
        'delegation_number',
        'delegation_entity',
        'is_transfer',
        'status',
        'bachelor_certificate',
        'remedial_statement',
        'notes',
        'rejection_reason',
        'exam_date',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'exam_date' => 'datetime',
            'is_transfer' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
