<?php

namespace App\Observers;

use App\Models\User;
use App\Notifications\ContactInfoChangedNotification;
use Illuminate\Notifications\AnonymousNotifiable;

/**
 * Watches student contact-detail changes. When a student's phone or email is
 * changed (admin edit or self-service), alert the likely account owner over
 * WhatsApp and/or Telegram so an unverified change can't silently enable a
 * phone-OTP takeover. Audit logging is handled separately by AuditObserver.
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
        // change. The Telegram chat id is not part of contact info, so it still
        // belongs to the real owner and is alerted too when linked.
        $alertPhone = in_array('phone', $changed, true)
            ? $user->getOriginal('phone')
            : $user->phone;

        if (empty($alertPhone) && empty($user->telegram_chat_id)) {
            return;
        }

        $target = new AnonymousNotifiable;

        if (! empty($alertPhone)) {
            $target->route('whatsapp', $alertPhone);
        }

        if (! empty($user->telegram_chat_id)) {
            $target->route('telegram', $user->telegram_chat_id);
        }

        // A security alert is a single message — no batch id, so it shows as a plain row.
        $target->notify(new ContactInfoChangedNotification($changed));
    }
}
