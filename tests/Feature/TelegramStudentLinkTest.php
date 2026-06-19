<?php

use App\Filament\Student\Pages\StudentDashboard;
use App\Models\Setting;
use App\Models\User;
use App\Services\TelegramVerificationService;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function (): void {
    config()->set('services.telegram.bot_token', 'TESTTOKEN');
    config()->set('services.telegram.bot_username', 'The_Creators67_bot');
    Setting::flushCache();
});

function linkService(): TelegramVerificationService
{
    return app(TelegramVerificationService::class);
}

it('starts a student link and reuses the open row', function () {
    $user = User::factory()->create();

    $verification = linkService()->startForUser($user);

    expect($verification->purpose)->toBe('student_link')
        ->and($verification->user_id)->toBe($user->id)
        ->and($verification->token)->not->toBeEmpty();

    expect(linkService()->startForUser($user)->id)->toBe($verification->id);
});

it('links the user on /start with no contact step', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]], 200)]);
    $user = User::factory()->create();
    $verification = linkService()->startForUser($user);

    linkService()->processUpdate([
        'update_id' => 1,
        'message' => ['chat' => ['id' => 777], 'from' => ['id' => 777], 'text' => '/start '.$verification->token],
    ]);

    expect($user->fresh()->telegram_chat_id)->toBe('777')
        ->and($user->fresh()->telegram_linked_at)->not->toBeNull()
        ->and($verification->fresh()->status)->toBe('verified');
    expect(linkService()->isUserLinked($user->fresh()))->toBeTrue();

    // A confirmation message — NOT a request-contact keyboard.
    Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
        && ! isset($request['reply_markup']['keyboard']));
});

it('unlinks a user and removes pending link rows', function () {
    $user = User::factory()->create(['telegram_chat_id' => '777', 'telegram_linked_at' => now()]);
    linkService()->startForUser($user);

    linkService()->unlinkUser($user->fresh());

    expect($user->fresh()->telegram_chat_id)->toBeNull()
        ->and($user->fresh()->telegram_linked_at)->toBeNull();
});

it('shows the link card before linking and a compact chip after', function () {
    $user = studentWithRecord()['user'];
    $this->actingAs($user);

    // Not linked: the call-to-action card is shown, no "linked" chip.
    Livewire::test(StudentDashboard::class)
        ->assertSee('استلم إشعاراتك عبر تيليجرام')
        ->assertDontSee('تيليجرام مربوط');

    // Linked: the big card is gone, only the compact chip remains.
    $user->forceFill(['telegram_chat_id' => '555000'])->save();

    Livewire::test(StudentDashboard::class)
        ->assertSee('تيليجرام مربوط')
        ->assertDontSee('استلم إشعاراتك عبر تيليجرام');
});

it('unlinks via a Filament confirmation action (not a native confirm)', function () {
    $user = studentWithRecord()['user'];
    $user->forceFill(['telegram_chat_id' => '555000', 'telegram_linked_at' => now()])->saveQuietly();
    $this->actingAs($user);

    Livewire::test(StudentDashboard::class)
        ->assertActionExists('unlinkTelegram')
        ->callAction('unlinkTelegram');

    expect($user->fresh()->telegram_chat_id)->toBeNull();
});

it('exposes a deep link from the student dashboard and can unlink', function () {
    $user = studentWithRecord()['user'];
    $this->actingAs($user);

    $component = Livewire::test(StudentDashboard::class);

    expect($component->instance()->telegramLinkingEnabled())->toBeTrue();

    $component->call('startTelegramLink');
    expect($component->get('telegramDeepLink'))->toContain('https://t.me/The_Creators67_bot?start=');

    $user->forceFill(['telegram_chat_id' => '999', 'telegram_linked_at' => now()])->save();
    expect($component->instance()->isTelegramLinked())->toBeTrue();

    $component->call('unlinkTelegram');
    expect($user->fresh()->telegram_chat_id)->toBeNull();
});
