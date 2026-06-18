<?php

use App\Models\Application;
use App\Models\Semester;
use App\Models\Setting;
use App\Models\TelegramVerification;
use App\Services\TelegramVerificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
    config()->set('services.telegram.bot_token', 'TESTTOKEN');
    config()->set('services.telegram.bot_username', 'The_Creators67_bot');
    config()->set('services.telegram.api_base', 'https://api.telegram.org');
    // The Setting static memo survives RefreshDatabase — reset for deterministic reads.
    Setting::flushCache();
});

function tgService(): TelegramVerificationService
{
    return app(TelegramVerificationService::class);
}

/** Fake a successful Telegram sendMessage reply. */
function fakeTelegramApi(): void
{
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]], 200)]);
}

it('starts a pending verification and reuses the open row', function () {
    $first = tgService()->start('0912345678');

    expect($first->status)->toBe('pending')
        ->and($first->token)->not->toBeEmpty()
        ->and($first->intended_phone)->toBe('0912345678');

    expect(tgService()->start('0912345678')->id)->toBe($first->id);
});

it('builds a deep link with the bot username and token', function () {
    $verification = tgService()->start('0912345678');

    expect(tgService()->deepLink($verification))
        ->toBe('https://t.me/The_Creators67_bot?start='.$verification->token);
});

it('binds the chat and sends a request-contact keyboard on /start', function () {
    fakeTelegramApi();
    $verification = tgService()->start('0912345678');

    tgService()->processUpdate([
        'update_id' => 1,
        'message' => ['chat' => ['id' => 999], 'from' => ['id' => 999], 'text' => '/start '.$verification->token],
    ]);

    $verification->refresh();
    expect($verification->chat_id)->toBe('999')
        ->and($verification->telegram_user_id)->toBe('999')
        ->and($verification->status)->toBe('awaiting_contact');

    Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
        && isset($request['reply_markup']['keyboard'][0][0]['request_contact']));
});

it('verifies when the shared contact matches the entered phone', function () {
    fakeTelegramApi();
    $verification = tgService()->start('0912345678');

    tgService()->processUpdate([
        'update_id' => 1,
        'message' => ['chat' => ['id' => 999], 'from' => ['id' => 999], 'text' => '/start '.$verification->token],
    ]);
    tgService()->processUpdate([
        'update_id' => 2,
        'message' => ['chat' => ['id' => 999], 'from' => ['id' => 999], 'contact' => ['user_id' => 999, 'phone_number' => '+218912345678']],
    ]);

    $verification->refresh();
    expect($verification->status)->toBe('verified')
        ->and($verification->verified_at)->not->toBeNull();
    expect(tgService()->hasVerified('0912345678'))->toBeTrue();
    expect(tgService()->chatIdForVerifiedPhone('0912345678'))->toBe('999');
});

it('rejects a shared contact whose number differs from the entered phone', function () {
    fakeTelegramApi();
    $verification = tgService()->start('0912345678');

    tgService()->processUpdate([
        'update_id' => 1,
        'message' => ['chat' => ['id' => 999], 'from' => ['id' => 999], 'text' => '/start '.$verification->token],
    ]);
    tgService()->processUpdate([
        'update_id' => 2,
        'message' => ['chat' => ['id' => 999], 'from' => ['id' => 999], 'contact' => ['user_id' => 999, 'phone_number' => '+218915555555']],
    ]);

    expect($verification->fresh()->status)->toBe('mismatch');
    expect(tgService()->hasVerified('0912345678'))->toBeFalse();
});

it('rejects a forwarded contact (anti-spoof: contact.user_id must equal sender)', function () {
    fakeTelegramApi();
    $verification = tgService()->start('0912345678');

    tgService()->processUpdate([
        'update_id' => 1,
        'message' => ['chat' => ['id' => 999], 'from' => ['id' => 999], 'text' => '/start '.$verification->token],
    ]);
    tgService()->processUpdate([
        'update_id' => 2,
        'message' => ['chat' => ['id' => 999], 'from' => ['id' => 999], 'contact' => ['user_id' => 555, 'phone_number' => '+218912345678']],
    ]);

    expect($verification->fresh()->status)->toBe('awaiting_contact');
    expect(tgService()->hasVerified('0912345678'))->toBeFalse();
});

it('notifies and stores nothing for an unknown /start token', function () {
    fakeTelegramApi();

    tgService()->processUpdate([
        'update_id' => 1,
        'message' => ['chat' => ['id' => 999], 'from' => ['id' => 999], 'text' => '/start bogustoken'],
    ]);

    expect(TelegramVerification::where('chat_id', '999')->exists())->toBeFalse();
    Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage'));
});

it('hasVerified respects the 30-minute grace window', function () {
    $verification = tgService()->start('0912345678');
    $verification->forceFill([
        'status' => 'verified',
        'telegram_phone' => '0912345678',
        'chat_id' => '1',
        'verified_at' => now()->subMinutes(40),
    ])->save();

    expect(tgService()->hasVerified('0912345678'))->toBeFalse();
});

it('reports verification state via status()', function () {
    $verification = tgService()->start('0912345678');
    expect(tgService()->status($verification->token)['verified'])->toBeFalse();

    $verification->forceFill(['status' => 'verified', 'telegram_phone' => '0912345678', 'verified_at' => now()])->save();

    $status = tgService()->status($verification->token);
    expect($status['verified'])->toBeTrue()->and($status['phone'])->toBe('0912345678');
});

it('POST /apply/telegram/init returns a deep link and creates a row', function () {
    $this->postJson(route('registration.telegram.init'), ['phone' => '0912345678'])
        ->assertSuccessful()
        ->assertJson(['ok' => true])
        ->assertJsonStructure(['deep_link', 'token']);

    expect(TelegramVerification::where('intended_phone', '0912345678')->exists())->toBeTrue();
});

it('init fails when Telegram is not configured', function () {
    config()->set('services.telegram.bot_token', null);

    $this->postJson(route('registration.telegram.init'), ['phone' => '0912345678'])
        ->assertStatus(422);
});

it('GET /apply/telegram/status reports a verified token', function () {
    $verification = tgService()->start('0912345678');
    $verification->forceFill(['status' => 'verified', 'telegram_phone' => '0912345678', 'verified_at' => now()])->save();

    $this->getJson(route('registration.telegram.status', ['token' => $verification->token]))
        ->assertSuccessful()
        ->assertJson(['verified' => true]);
});

it('telegram:poll drains updates and advances the offset', function () {
    Cache::forget('telegram:poll:offset');
    $verification = tgService()->start('0912345678');

    Http::fake([
        'api.telegram.org/*getUpdates*' => Http::response(['ok' => true, 'result' => [
            ['update_id' => 10, 'message' => ['chat' => ['id' => 999], 'from' => ['id' => 999], 'text' => '/start '.$verification->token]],
        ]], 200),
        'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]], 200),
    ]);

    $this->artisan('telegram:poll')->assertSuccessful();

    expect($verification->fresh()->status)->toBe('awaiting_contact')
        ->and((int) Cache::get('telegram:poll:offset'))->toBe(11);
});

it('allows submission when verified via Telegram and stores the chat id on the application', function () {
    Semester::factory()->active()->create();
    Setting::set('apply_otp_required', '1');

    $verification = tgService()->start('0915555555');
    $verification->forceFill([
        'status' => 'verified',
        'telegram_phone' => '0915555555',
        'chat_id' => '424242',
        'verified_at' => now(),
    ])->save();

    $this->post(route('registration.store'), validApplicationPayload(['phone' => '0915555555']))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('registration.create'));

    $application = Application::where('phone', '0915555555')->first();
    expect($application)->not->toBeNull()
        ->and($application->telegram_chat_id)->toBe('424242');
});

it('blocks submission when neither WhatsApp nor Telegram verified the phone', function () {
    Semester::factory()->active()->create();
    Setting::set('apply_otp_required', '1');

    $this->from(route('registration.create'))
        ->post(route('registration.store'), validApplicationPayload(['phone' => '0915555555']))
        ->assertRedirect(route('registration.create'))
        ->assertSessionHasErrors('phone');

    expect(Application::where('phone', '0915555555')->exists())->toBeFalse();
});
