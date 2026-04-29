<?php

namespace App\Models;

use App\Enums\ThesisStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThesisRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'title',
        'title_en',
        'supervisor_name',
        'supervisor_degree',
        'supervisor_employer',
        'supervisor_id',
        'faculty_supervisor_id',
        'is_external_supervisor',
        'status',
        'decided_by',
        'decided_at',
        'official_form_path',
        'proposal_path',
        'rejection_reason',
        'printed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ThesisStatus::class,
            'decided_at' => 'datetime',
            'printed_at' => 'datetime',
            'is_external_supervisor' => 'boolean',
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

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Supervisor::class);
    }

    public function facultySupervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'faculty_supervisor_id');
    }

    public function getSupervisorDisplayName(): string
    {
        if ($this->is_external_supervisor && $this->supervisor) {
            return $this->supervisor->name;
        }

        if (! $this->is_external_supervisor && $this->facultySupervisor) {
            return $this->facultySupervisor->name;
        }

        return $this->supervisor_name ?? '—';
    }
}
