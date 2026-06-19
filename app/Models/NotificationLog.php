<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'batch_id',
        'channel',
        'phone',
        'event',
        'message',
        'status',
        'error_message',
        'provider_message_id',
        'sent_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'sent_at' => 'datetime',
            'created_at' => 'datetime',
            'last_sent_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
