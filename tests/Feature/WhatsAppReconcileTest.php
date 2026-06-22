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

it('never records a WhatsApp send as sent — only reconcile can confirm delivery', function () {
    \App\Models\Setting::flushCache();
    // Qonvo returns 2xx with an optimistic "sent" — which is NOT proof of delivery.
    Http::fake(['*' => Http::response(['data' => ['status' => 'sent', 'id' => 999]], 200)]);

    $notifiable = new class
    {
        public string $name = 'أحمد';

        public string $phone = '0912345678';

        public function routeNotificationFor(string $channel): mixed
        {
            return null;
        }
    };

    $notification = new class extends \Illuminate\Notifications\Notification
    {
        public string $logEvent = 'welcome_student';

        public function toWhatsApp(object $notifiable): \App\Channels\WhatsAppMessage
        {
            return new \App\Channels\WhatsAppMessage(message: 'مرحبا');
        }
    };

    app(\App\Channels\WhatsAppChannel::class)->send($notifiable, $notification);

    $log = NotificationLog::query()->where('channel', 'whatsapp')->sole();

    // Logged as pending (queued) with the provider id — sent is reachable ONLY via reconcile.
    expect($log->status)->toBe('pending')
        ->and($log->provider_message_id)->toBe('999');
});

it('strips a leading + from the sender phone so Qonvo recognises the registered number', function () {
    config()->set('services.qonvo.sender_phone', '+218911011851');
    Http::fake(['*' => Http::response(['data' => ['id' => 1, 'status' => 'pending']], 201)]);

    app(WhatsAppService::class)->sendText('0912345678', new \App\Channels\WhatsAppMessage(message: 'hi'));

    Http::assertSent(fn (\Illuminate\Http\Client\Request $request): bool => $request['sender_whatsapp_number_phone'] === '218911011851'
    );
});

it('maps Qonvo statuses to log statuses', function () {
    expect(WhatsAppService::mapStatus('delivered'))->toBe('sent')
        ->and(WhatsAppService::mapStatus('sent'))->toBe('sent')
        ->and(WhatsAppService::mapStatus('failed'))->toBe('failed')
        ->and(WhatsAppService::mapStatus('rejected'))->toBe('failed')
        ->and(WhatsAppService::mapStatus('pending'))->toBe('pending')
        ->and(WhatsAppService::mapStatus(null))->toBe('pending');
});
