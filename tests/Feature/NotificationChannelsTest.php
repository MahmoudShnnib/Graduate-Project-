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

it('welcome message combines exam-pass acceptance with the account details and omits any password', function () {
    $student = new Student(['enrollment_no' => 'CS-2026-001']);
    $notifiable = (object) ['name' => 'محمد علي', 'email' => 'm@pgsms.test', 'phone' => '0912345678'];

    $text = (new WelcomeStudentNotification($student))->toWhatsApp($notifiable)->message;

    expect($text)->toContain('اجتزت اختبار القبول')
        ->and($text)->toContain('CS-2026-001')
        ->and($text)->toContain('m@pgsms.test')
        ->and($text)->not->toContain('كلمة المرور');
});
