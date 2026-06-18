<?php

use App\Models\User;
use App\Notifications\ContactInfoChangedNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

it('alerts the OLD phone when a student phone is changed', function () {
    $student = User::factory()->withRole('student')->create(['phone' => '0911111111']);

    $student->update(['phone' => '0922222222']);

    Notification::assertSentOnDemand(
        ContactInfoChangedNotification::class,
        function (ContactInfoChangedNotification $n, array $channels, object $notifiable) {
            return $notifiable->routes['whatsapp'] === '0911111111'
                && $n->changedFields === ['phone'];
        },
    );
});

it('alerts the current phone when only the email is changed', function () {
    $student = User::factory()->withRole('student')->create([
        'phone' => '0911111111',
        'email' => 'old@example.ly',
    ]);

    $student->update(['email' => 'new@example.ly']);

    Notification::assertSentOnDemand(
        ContactInfoChangedNotification::class,
        fn (ContactInfoChangedNotification $n, array $channels, object $notifiable) => $notifiable->routes['whatsapp'] === '0911111111'
            && $n->changedFields === ['email'],
    );
});

it('also alerts the linked Telegram chat over both channels', function () {
    config()->set('services.qonvo.api_token', 'q-token');
    config()->set('services.telegram.bot_token', 't-token');

    $student = User::factory()->withRole('student')->create([
        'phone' => '0911111111',
        'telegram_chat_id' => '5550001',
    ]);

    $student->update(['phone' => '0922222222']);

    Notification::assertSentOnDemand(
        ContactInfoChangedNotification::class,
        function (ContactInfoChangedNotification $n, array $channels, object $notifiable) {
            return ($notifiable->routes['whatsapp'] ?? null) === '0911111111'
                && ($notifiable->routes['telegram'] ?? null) === '5550001'
                && in_array(App\Channels\WhatsAppChannel::class, $channels, true)
                && in_array(App\Channels\TelegramChannel::class, $channels, true);
        },
    );
});

it('alerts only Telegram when the student has no phone on record', function () {
    config()->set('services.telegram.bot_token', 't-token');

    $student = User::factory()->withRole('student')->create([
        'phone' => null,
        'email' => 'old@example.ly',
        'telegram_chat_id' => '5550002',
    ]);

    $student->update(['email' => 'new@example.ly']);

    Notification::assertSentOnDemand(
        ContactInfoChangedNotification::class,
        fn (ContactInfoChangedNotification $n, array $channels, object $notifiable) => ! isset($notifiable->routes['whatsapp'])
            && ($notifiable->routes['telegram'] ?? null) === '5550002'
            && $channels === [App\Channels\TelegramChannel::class],
    );
});

it('does not alert when a non-contact field changes', function () {
    $student = User::factory()->withRole('student')->create(['phone' => '0911111111']);

    $student->update(['name' => 'اسم جديد']);

    Notification::assertNothingSent();
});

it('does not alert for non-student users', function () {
    $admin = User::factory()->withRole('super_admin')->create(['phone' => '0911111111']);

    $admin->update(['phone' => '0922222222']);

    Notification::assertNothingSent();
});
