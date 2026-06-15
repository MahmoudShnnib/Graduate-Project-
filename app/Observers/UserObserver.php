<?php

namespace App\Observers;

use App\Models\User;
use App\Notifications\ContactInfoChangedNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Watches student contact-detail changes. When a student's phone or email is
 * changed (admin edit or self-service), alert the likely account owner over
 * WhatsApp so an unverified change can't silently enable a phone-OTP takeover.
 * Audit logging of the same change is handled separately by AuditObserver.
 */
class UserObserver
{
    public function updated(User $user): void
    {
        if ($user->role !== 'student') {
            return;
        }

        $changed = [];

        if ($user->wasChanged('phone')) {
            $changed[] = 'phone';
        }

        if ($user->wasChanged('email')) {
            $changed[] = 'email';
        }

        if ($changed === []) {
            return;
        }

        // Alert the OLD phone when the phone itself changed (the new number may
        // belong to an attacker); otherwise the current phone for an email-only
        // change. No phone on record means there's no one to warn.
        $alertPhone = in_array('phone', $changed, true)
            ? $user->getOriginal('phone')
            : $user->phone;

        if (empty($alertPhone)) {
            return;
        }

        Notification::route('whatsapp', $alertPhone)
            ->notify(new ContactInfoChangedNotification($changed));
    }
}
