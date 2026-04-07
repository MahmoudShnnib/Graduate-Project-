<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

class Application extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'specialization_id',
        'tracking_code',
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
        'exam_score',
        'exam_passed',
        'exam_result_date',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'exam_date' => 'datetime',
            'is_transfer' => 'boolean',
            'exam_score' => 'decimal:2',
            'exam_passed' => 'boolean',
            'exam_result_date' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function specialization(): BelongsTo
    {
        return $this->belongsTo(Specialization::class);
    }

    /**
     * Route notifications for the mail channel.
     */
    public function routeNotificationForMail(): string
    {
        return $this->email;
    }

    /**
     * Route notifications for the WhatsApp channel.
     */
    public function routeNotificationForWhatsapp(): ?string
    {
        return $this->phone;
    }
}
