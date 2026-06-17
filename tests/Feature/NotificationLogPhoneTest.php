<?php

use App\Channels\WhatsAppChannel;
use App\Models\NotificationLog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.qonvo.api_token', 'test-token');
    config()->set('services.qonvo.sender_phone', '0911111111');
});

it('logs a sent notification with the recipient phone number', function () {
    Http::fake(['*' => Http::response(['status' => 'ok'], 200)]);

    ['user' => $user, 'student' => $student] = studentWithRecord();
    $user->update(['phone' => '0912345678']);
    $user->refresh();

    // Ignore any incidental notifications emitted during fixture setup.
    NotificationLog::query()->delete();

    app(WhatsAppChannel::class)->send($user, new App\Notifications\WelcomeStudentNotification($student));

    $log = NotificationLog::first();

    expect($log)->not->toBeNull()
        ->and($log->status)->toBe('sent')
        ->and($log->phone)->toBe('0912345678');
});

it('logs a failed notification (never "sent") when the provider errors, keeping the phone', function () {
    Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));

    ['user' => $user, 'student' => $student] = studentWithRecord();
    $user->update(['phone' => '0912345678']);
    $user->refresh();

    // Ignore any incidental notifications emitted during fixture setup.
    NotificationLog::query()->delete();

    app(WhatsAppChannel::class)->send($user, new App\Notifications\WelcomeStudentNotification($student));

    $log = NotificationLog::first();

    expect($log)->not->toBeNull()
        ->and($log->status)->toBe('failed')
        ->and($log->phone)->toBe('0912345678')
        ->and($log->error_message)->not->toBeNull();
});
