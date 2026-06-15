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
