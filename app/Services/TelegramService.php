<?php

namespace App\Services;

use App\Channels\TelegramMessage;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class TelegramService
{
    /**
     * Send a text message to a Telegram chat. Unlike Qonvo, Telegram's
     * sendMessage returns the real outcome synchronously — a 2xx means it was
     * delivered, a 4xx (e.g. 403 "bot was blocked by the user") means it was
     * not — so no later reconciliation is needed.
     *
     * @return array<string, mixed>
     */
    public function sendText(int|string $chatId, TelegramMessage $message): array
    {
        return $this->sendMessage($chatId, $message->message, parseMode: $message->parseMode);
    }

    /**
     * Low-level sendMessage with an optional reply markup (e.g. a request-contact
     * keyboard). Throws on a non-2xx response (4xx like 403 = blocked by user).
     *
     * @param  array<string, mixed>|null  $replyMarkup
     * @return array<string, mixed>
     */
    public function sendMessage(int|string $chatId, string $text, ?array $replyMarkup = null, ?string $parseMode = null): array
    {
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        if ($parseMode !== null) {
            $payload['parse_mode'] = $parseMode;
        }

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = $replyMarkup;
        }

        $response = Http::acceptJson()
            ->connectTimeout(5)
            ->timeout(10)
            ->post($this->endpoint('sendMessage'), $payload);

        $response->throw();

        return $response->json();
    }

    /**
     * Pull new updates (long-poll when $timeout > 0). $offset acknowledges every
     * update with a lower id, so each is processed once.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUpdates(int $offset = 0, int $timeout = 0): array
    {
        $response = Http::acceptJson()
            ->connectTimeout(15)
            ->timeout($timeout + 15)
            ->retry(2, 1000, throw: false)
            ->get($this->endpoint('getUpdates'), array_filter([
                'offset' => $offset > 0 ? $offset : null,
                'timeout' => $timeout > 0 ? $timeout : null,
                'allowed_updates' => json_encode(['message']),
            ], fn ($v) => $v !== null));

        if (! $response->successful()) {
            return [];
        }

        return $response->json('result', []);
    }

    /**
     * A one-time reply keyboard with a single "share my phone number" button.
     *
     * @return array<string, mixed>
     */
    public function requestContactKeyboard(string $buttonText): array
    {
        return [
            'keyboard' => [[['text' => $buttonText, 'request_contact' => true]]],
            'resize_keyboard' => true,
            'one_time_keyboard' => true,
        ];
    }

    public function isConfigured(): bool
    {
        return ! empty(config('services.telegram.bot_token'));
    }

    /**
     * Whether Telegram may actually be used: configured AND not closed by the admin
     * (the `telegram_enabled` setting).
     */
    public function isEnabled(): bool
    {
        return $this->isConfigured() && Setting::get('telegram_enabled', '1') === '1';
    }

    public function botUsername(): ?string
    {
        return config('services.telegram.bot_username');
    }

    /**
     * Normalise any Libyan phone form (+218…, 218…, 0…) to the canonical local
     * 09XXXXXXXX so an entered number and a Telegram-shared contact can be compared.
     */
    public static function toLocalLibyan(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (str_starts_with($digits, '218')) {
            $digits = '0'.substr($digits, 3);
        }

        if (! str_starts_with($digits, '0')) {
            $digits = '0'.$digits;
        }

        return $digits;
    }

    private function endpoint(string $method): string
    {
        return rtrim((string) config('services.telegram.api_base'), '/')
            .'/bot'.config('services.telegram.bot_token').'/'.$method;
    }
}
