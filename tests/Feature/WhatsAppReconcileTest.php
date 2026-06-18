<?php

use App\Models\NotificationLog;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.qonvo.api_token', 'test-token');
    config()->set('services.qonvo.sender_phone', '0911111111');
    config()->set('services.qonvo.base_url', 'https://qonvo.ly/api');
});

function pendingWhatsAppLog(string|int $providerId = 555): NotificationLog
{
    ['student' => $student] = studentWithRecord();
    NotificationLog::query()->delete();

    return NotificationLog::create([
        'student_id' => $student->id,
        'channel' => 'whatsapp',
        'phone' => '0912345678',
        'event' => 'welcome_student',
        'message' => 'test',
        'status' => 'pending',
        'provider_message_id' => (string) $providerId,
        'sent_at' => now(),
        'created_at' => now(),
    ]);
}

it('flips a pending log to failed with Qonvo error message', function () {
    Http::fake(['*' => Http::response([
        'id' => 555,
        'status' => 'failed',
        'error_message' => 'Failed to send message through WhatsApp service',
    ], 200)]);

    $log = pendingWhatsAppLog();

    $updated = app(WhatsAppService::class)->reconcilePendingLogs();

    expect($updated)->toBe(1)
        ->and($log->fresh()->status)->toBe('failed')
        ->and($log->fresh()->error_message)->toBe('Failed to send message through WhatsApp service');
});

it('flips a pending log to sent when Qonvo reports delivered', function () {
    Http::fake(['*' => Http::response(['id' => 555, 'status' => 'delivered'], 200)]);

    $log = pendingWhatsAppLog();

    $updated = app(WhatsAppService::class)->reconcilePendingLogs();

    expect($updated)->toBe(1)
        ->and($log->fresh()->status)->toBe('sent');
});

it('leaves a log pending while Qonvo still reports pending', function () {
    Http::fake(['*' => Http::response(['id' => 555, 'status' => 'pending'], 200)]);

    $log = pendingWhatsAppLog();

    $updated = app(WhatsAppService::class)->reconcilePendingLogs();

    expect($updated)->toBe(0)
        ->and($log->fresh()->status)->toBe('pending');
});

it('maps Qonvo statuses to log statuses', function () {
    expect(WhatsAppService::mapStatus('delivered'))->toBe('sent')
        ->and(WhatsAppService::mapStatus('sent'))->toBe('sent')
        ->and(WhatsAppService::mapStatus('failed'))->toBe('failed')
        ->and(WhatsAppService::mapStatus('rejected'))->toBe('failed')
        ->and(WhatsAppService::mapStatus('pending'))->toBe('pending')
        ->and(WhatsAppService::mapStatus(null))->toBe('pending');
});
