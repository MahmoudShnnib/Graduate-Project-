<?php

namespace App\Filament\Resources\AuditLogs\Pages;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Models\AuditLog;
use App\Services\AuditService;
use Filament\Resources\Pages\ViewRecord;

class ViewAuditLog extends ViewRecord
{
    protected static string $resource = AuditLogResource::class;

    protected string $view = 'filament.resources.audit-logs.pages.view-audit-log';

    public function getTitle(): string
    {
        return 'سجل التدقيق';
    }

    protected function resolveRecord(int|string $key): AuditLog
    {
        return AuditLog::with('user')->findOrFail($key);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getModelLabel(): string
    {
        $type = $this->record->model_type;

        if (! $type) {
            return '—';
        }

        return AuditLogResource::MODEL_LABELS[$type] ?? class_basename($type);
    }

    public function getActionLabel(): string
    {
        return AuditService::actionLabel($this->record->action);
    }

    public function getRoleLabel(): string
    {
        $role = $this->record->user?->role;

        if (! $role) {
            return '—';
        }

        return AuditLogResource::ROLE_LABELS[(string) $role] ?? (string) $role;
    }

    public function formatValue(string $key, mixed $value): string
    {
        return AuditLogResource::formatFieldValue($key, $value);
    }

    public function getFieldLabel(string $key): string
    {
        return AuditLogResource::FIELD_LABELS[$key] ?? $key;
    }
}
