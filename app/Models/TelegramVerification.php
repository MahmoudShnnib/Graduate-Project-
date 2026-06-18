<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramVerification extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'purpose',
        'intended_phone',
        'chat_id',
        'telegram_phone',
        'telegram_user_id',
        'status',
        'verified_at',
        'expires_at',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
