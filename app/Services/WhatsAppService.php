<?php

namespace App\Services;

use App\Channels\WhatsAppMessage;
use App\Models\NotificationLog;
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
     * Re-query Qonvo for the real delivery status of a previously-queued message.
     * The send endpoint only returns "pending" (queued); the final sent/failed
     * outcome is available here by the message id Qonvo returned.
     *
     * @return array{status: ?string, error_message: ?string}|null null when unreachable
     */
    public function getMessageStatus(int|string $providerMessageId): ?array
    {
        $response = Http::withToken(config('services.qonvo.api_token'))
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(10)
            ->get(config('services.qonvo.base_url').'/whatsapp/messages/'.$providerMessageId);

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        return [
            'status' => $data['status'] ?? null,
            'error_message' => $data['error_message'] ?? null,
        ];
    }

    /**
     * Map a Qonvo delivery status to our NotificationLog status. 'pending' means
     * queued at Qonvo — accepted by the API but delivery not yet confirmed.
     */
    public static function mapStatus(?string $qonvoStatus): string
    {
        return match (strtolower((string) $qonvoStatus)) {
            'sent', 'delivered', 'read', 'success' => 'sent',
            'failed', 'rejected', 'error', 'undelivered' => 'failed',
            default => 'pending',
        };
    }

    /**
     * Re-check Qonvo for every WhatsApp log still 'pending' and flip it to the real
     * delivery status (sent / failed). Returns the number of rows updated.
     */
    public function reconcilePendingLogs(int $limit = 200): int
    {
        $logs = NotificationLog::query()
            ->where('channel', 'whatsapp')
            ->where('status', 'pending')
            ->whereNotNull('provider_message_id')
            ->latest('sent_at')
            ->limit($limit)
            ->get();

        $updated = 0;

        foreach ($logs as $log) {
            $remote = $this->getMessageStatus($log->provider_message_id);

            if ($remote === null) {
                continue;
            }

            $status = self::mapStatus($remote['status']);

            if ($status === 'pending') {
                continue;
            }

            $log->update([
                'status' => $status,
                'error_message' => $remote['error_message'] ?? $log->error_message,
            ]);

            $updated++;
        }

        return $updated;
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
