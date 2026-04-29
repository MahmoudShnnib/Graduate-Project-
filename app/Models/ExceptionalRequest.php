<?php

namespace App\Models;

use App\Enums\ExceptionalRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExceptionalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'reason',
        'status',
        'decided_by',
        'decided_at',
        'decision_notes',
        'summon_message',
        'summon_sent_at',
        'can_reapply',
    ];

    protected function casts(): array
    {
        return [
            'status' => ExceptionalRequestStatus::class,
            'decided_at' => 'datetime',
            'summon_sent_at' => 'datetime',
            'can_reapply' => 'boolean',
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
