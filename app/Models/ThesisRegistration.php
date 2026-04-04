<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThesisRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'title',
        'supervisor_name',
        'supervisor_degree',
        'supervisor_employer',
        'status',
        'decided_by',
        'decided_at',
        'official_form_path',
    ];

    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
