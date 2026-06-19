<?php

use App\Channels\TelegramChannel;
use App\Channels\WhatsAppChannel;
use App\Models\Setting;
use App\Notifications\Concerns\HasMessagingChannels;
use Illuminate\Notifications\Notification;

beforeEach(fn () => Setting::flushCache());

function policyNotification(): Notification
{
    return new class extends Notification
    {
        use HasMessagingChannels;
    };
}

function notifiableWith(?string $phone, ?string $chatId): object
{
    return new class($phone, $chatId)
    {
        public function __construct(public ?string $phone, public ?string $telegram_chat_id) {}
    };
}

function configureChannels(bool $whatsapp, bool $telegram): void
{
    config()->set('services.qonvo.api_token', $whatsapp ? 'q-token' : null);
    config()->set('services.telegram.bot_token', $telegram ? 't-token' : null);
}

it('whatsapp_first prefers WhatsApp when both are available', function () {
    configureChannels(true, true);
    Setting::set('notification_channel_policy', 'whatsapp_first');

    expect(policyNotification()->via(notifiableWith('0911111111', '123')))->toBe([WhatsAppChannel::class]);
});

it('whatsapp_first falls back to Telegram when WhatsApp is unavailable', function () {
    configureChannels(false, true);
    Setting::set('notification_channel_policy', 'whatsapp_first');

    expect(policyNotification()->via(notifiableWith('0911111111', '123')))->toBe([TelegramChannel::class]);
});

it('telegram_first prefers Telegram when both are available', function () {
    configureChannels(true, true);
    Setting::set('notification_channel_policy', 'telegram_first');

    expect(policyNotification()->via(notifiableWith('0911111111', '123')))->toBe([TelegramChannel::class]);
});

it('telegram_first falls back to WhatsApp when the user has no linked chat', function () {
    configureChannels(true, true);
    Setting::set('notification_channel_policy', 'telegram_first');

    expect(policyNotification()->via(notifiableWith('0911111111', null)))->toBe([WhatsAppChannel::class]);
});

it('both returns both channels when available', function () {
    configureChannels(true, true);
    Setting::set('notification_channel_policy', 'both');

    expect(policyNotification()->via(notifiableWith('0911111111', '123')))
        ->toBe([WhatsAppChannel::class, TelegramChannel::class]);
});

it('defaults to BOTH channels when no policy is set (so a linked Telegram always gets it)', function () {
    configureChannels(true, true);
    // No Setting::set — exercise the default.

    expect(policyNotification()->via(notifiableWith('0911111111', '123')))
        ->toBe([WhatsAppChannel::class, TelegramChannel::class]);
});

it('drops WhatsApp when the admin has closed that channel', function () {
    configureChannels(true, true);
    Setting::set('notification_channel_policy', 'both');
    Setting::set('whatsapp_enabled', '0');

    expect(policyNotification()->via(notifiableWith('0911111111', '123')))->toBe([TelegramChannel::class]);
});

it('drops Telegram when the admin has closed that channel', function () {
    configureChannels(true, true);
    Setting::set('notification_channel_policy', 'both');
    Setting::set('telegram_enabled', '0');

    expect(policyNotification()->via(notifiableWith('0911111111', '123')))->toBe([WhatsAppChannel::class]);
});

it('sends nothing when both channels are closed', function () {
    configureChannels(true, true);
    Setting::set('whatsapp_enabled', '0');
    Setting::set('telegram_enabled', '0');

    expect(policyNotification()->via(notifiableWith('0911111111', '123')))->toBe([]);
});

it('returns no channels when nothing is configured or reachable', function () {
    configureChannels(false, false);

    expect(policyNotification()->via(notifiableWith(null, null)))->toBe([]);
});
