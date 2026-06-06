<?php

namespace App\Services;

use App\Channels\WhatsAppMessage;
use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    public function sendText(string $recipientPhone, WhatsAppMessage $message): array
    {
        $response = Http::withToken(config('services.qonvo.api_token'))
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(10)
            ->post(config('services.qonvo.base_url').'/whatsapp/messages/send', [
                'sender_whatsapp_number_phone' => config('services.qonvo.sender_phone'),
                'recipient_number' => $this->formatPhone($recipientPhone),
                'type' => $message->type,
                'message' => $message->message,
            ]);

        $response->throw();

        return $response->json();
    }

    public function isConfigured(): bool
    {
        return ! empty(config('services.qonvo.api_token'))
            && ! empty(config('services.qonvo.sender_phone'));
    }

    /**
     * Convert local Libyan phone (09XXXXXXXX) to international format (+2189XXXXXXXX).
     */
    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '218'.substr($phone, 1);
        }

        if (! str_starts_with($phone, '218')) {
            $phone = '218'.$phone;
        }

        return '+'.$phone;
    }
}
