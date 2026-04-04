<?php

namespace App\Models;

use App\Enums\SuspensionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuspensionRequest extends Model
{
    protected $fillable = [
        'student_id',
        'semester_id',
        'reason',
        'status',
        'decided_by',
        'decided_at',
        'decision_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => SuspensionStatus::class,
            'decided_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
