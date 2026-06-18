<?php

namespace App\Filament\Resources\NotificationLogs\Pages;

use App\Filament\Resources\NotificationLogs\NotificationLogResource;
use App\Models\NotificationLog;
use App\Services\WhatsAppService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListNotificationLogs extends ListRecords
{
    protected static string $resource = NotificationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reconcile')
                ->label('تحديث حالات الإرسال')
                ->icon(Heroicon::ArrowPath)
                ->color('gray')
                ->badge(fn (): ?int => $this->pendingCount() ?: null)
                ->tooltip('إعادة فحص الرسائل قيد الإرسال لدى Qonvo وتحديث حالتها (تم الإرسال / فشل)')
                ->action(function (WhatsAppService $whatsApp): void {
                    if (! $whatsApp->isConfigured()) {
                        Notification::make()->title('خدمة الواتساب غير مهيأة')->warning()->send();

                        return;
                    }

                    $updated = $whatsApp->reconcilePendingLogs();

                    Notification::make()
                        ->title($updated > 0 ? "تم تحديث حالة {$updated} رسالة" : 'لا توجد رسائل بحاجة لتحديث')
                        ->success()
                        ->send();
                }),
        ];
    }

    private function pendingCount(): int
    {
        return NotificationLog::where('channel', 'whatsapp')
            ->where('status', 'pending')
            ->whereNotNull('provider_message_id')
            ->count();
    }
}
