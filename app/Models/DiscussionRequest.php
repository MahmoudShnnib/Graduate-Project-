<?php

namespace App\Models;

use App\Enums\DiscussionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscussionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'thesis_registration_id',
        'status',
        'discussion_date',
        'rejection_reason',
        'decided_by',
        'decided_at',
        'discussed_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DiscussionStatus::class,
            'discussion_date' => 'date',
            'decided_at' => 'datetime',
            'discussed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function thesisRegistration(): BelongsTo
    {
        return $this->belongsTo(ThesisRegistration::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
