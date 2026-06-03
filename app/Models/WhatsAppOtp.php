<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppOtp extends Model
{
    protected $table = 'whats_app_otps';

    protected $fillable = [
        'phone',
        'purpose',
        'code_hash',
        'attempts',
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
            'attempts' => 'integer',
            'verified_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
