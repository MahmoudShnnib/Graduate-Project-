<?php

use App\Channels\TelegramChannel;
use App\Channels\TelegramMessage;
use App\Models\Application;
use App\Models\NotificationLog;
use App\Models\Student;
use App\Notifications\ApplicationInitiallyApprovedNotification;
use App\Notifications\WelcomeStudentNotification;
use App\Services\TelegramService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config()->set('services.telegram.bot_token', 'TESTTOKEN');
    config()->set('services.telegram.bot_username', 'GradStudiesBot');
    config()->set('services.telegram.api_base', 'https://api.telegram.org');
});

/** A minimal notifiable carrying a telegram_chat_id, decoupled from the DB. */
function telegramNotifiable(?int $chatId = 555000): object
{
    return new class($chatId)
    {
        public string $name = 'أحمد علي';

        public string $email = 'ahmed@example.ly';

        public string $phone = '0911234567';

        public function __construct(public mixed $telegram_chat_id) {}

        public function routeNotificationFor(string $channel): mixed
        {
            return null;
        }
    };
}

/** A tiny notification with a fixed Telegram body. */
function telegramNotification(): Notification
{
    return new class extends Notification
    {
        public string $logEvent = 'test_event';

        public function toTelegram(object $notifiable): TelegramMessage
        {
            return new TelegramMessage(message: 'نص تجريبي');
        }
    };
}

it('reports configured only when a bot token is set', function (): void {
    expect(app(TelegramService::class)->isConfigured())->toBeTrue();

    config()->set('services.telegram.bot_token', null);

    expect(app(TelegramService::class)->isConfigured())->toBeFalse();
});

it('sends text to the Telegram bot API with the right payload', function (): void {
    Http::fake([
        'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 42]], 200),
    ]);

    app(TelegramService::class)->sendText(555000, new TelegramMessage(message: 'مرحبا'));

    Http::assertSent(function ($request): bool {
        return str_contains($request->url(), '/botTESTTOKEN/sendMessage')
            && $request['chat_id'] === 555000
            && $request['text'] === 'مرحبا';
    });
});

it('logs a sent NotificationLog with the provider message id on success', function (): void {
    Http::fake([
        'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 9876]], 200),
    ]);

    app(TelegramChannel::class)->send(telegramNotifiable(), telegramNotification());

    $log = NotificationLog::query()->where('channel', 'telegram')->sole();

    expect($log->status)->toBe('sent')
        ->and($log->provider_message_id)->toBe('9876')
        ->and($log->event)->toBe('test_event')
        ->and($log->phone)->toBe('0911234567');
});

it('logs a failed NotificationLog when the user has blocked the bot', function (): void {
    Http::fake([
        'api.telegram.org/*' => Http::response([
            'ok' => false,
            'error_code' => 403,
            'description' => 'Forbidden: bot was blocked by the user',
        ], 403),
    ]);

    app(TelegramChannel::class)->send(telegramNotifiable(), telegramNotification());

    $log = NotificationLog::query()->where('channel', 'telegram')->sole();

    expect($log->status)->toBe('failed')
        ->and($log->provider_message_id)->toBeNull();
});

it('does nothing when Telegram is not configured', function (): void {
    config()->set('services.telegram.bot_token', null);
    Http::fake();

    app(TelegramChannel::class)->send(telegramNotifiable(), telegramNotification());

    Http::assertNothingSent();
    expect(NotificationLog::query()->count())->toBe(0);
});

it('does nothing when the notifiable has no chat id', function (): void {
    Http::fake();

    app(TelegramChannel::class)->send(telegramNotifiable(null), telegramNotification());

    Http::assertNothingSent();
    expect(NotificationLog::query()->count())->toBe(0);
});

it('builds a Telegram body for the welcome notification', function (): void {
    $student = Student::factory()->make(['enrollment_no' => 'GS-2026-001']);
    $message = (new WelcomeStudentNotification($student))->toTelegram(telegramNotifiable());

    expect($message)->toBeInstanceOf(TelegramMessage::class)
        ->and($message->message)->toContain('أحمد علي')
        ->and($message->message)->toContain('GS-2026-001');
});

it('builds a Telegram body for the initial-approval notification', function (): void {
    $application = Application::factory()->make();
    $message = (new ApplicationInitiallyApprovedNotification($application))->toTelegram(telegramNotifiable());

    expect($message)->toBeInstanceOf(TelegramMessage::class)
        ->and($message->message)->toContain('قبولك مبدئياً');
});
