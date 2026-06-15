<?php

use App\Channels\WhatsAppChannel;
use App\Models\Student;
use App\Notifications\WelcomeStudentNotification;

it('never routes notifications through the mail channel (WhatsApp-only by design)', function () {
    config()->set('services.qonvo.api_token', 'test-token');

    $notifiable = (object) ['phone' => '0912345678'];
    $notification = new WelcomeStudentNotification(new Student);

    $channels = $notification->via($notifiable);

    expect($channels)->not->toContain('mail')
        ->and($channels)->toContain(WhatsAppChannel::class);
});

it('returns no channels when WhatsApp is unavailable (no silent mail fallback)', function () {
    config()->set('services.qonvo.api_token', null);

    $notifiable = (object) ['phone' => '0912345678'];
    $notification = new WelcomeStudentNotification(new Student);

    expect($notification->via($notifiable))->toBe([]);
});
