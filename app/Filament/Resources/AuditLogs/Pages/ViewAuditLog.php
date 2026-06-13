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

    public function getActionDescription(): string
    {
        return AuditService::actionDescription($this->record->action);
    }

    /**
     * The other audit rows written by the same operation (same batch), so the full
     * fan-out of one action (e.g. accepting a student) is visible in one place.
     *
     * @return \Illuminate\Support\Collection<int, AuditLog>
     */
    public function getBatchSiblings(): \Illuminate\Support\Collection
    {
        if (! $this->record->batch_id) {
            return collect();
        }

        return AuditLog::query()
            ->where('batch_id', $this->record->batch_id)
            ->whereKeyNot($this->record->getKey())
            ->orderBy('id')
            ->get();
    }

    public function describeSibling(AuditLog $log): string
    {
        $label = AuditService::actionLabel($log->action);

        return $log->subject_label ? $label.' — '.$log->subject_label : $label;
    }

    public function siblingUrl(AuditLog $log): string
    {
        return AuditLogResource::getUrl('view', ['record' => $log->getKey()]);
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

    /**
     * URL to the underlying record (e.g. /admin/applications/9), or null
     * when the model type isn't mapped or the record was deleted.
     */
    public function getRecordUrl(): ?string
    {
        $type = $this->record->model_type;
        $id = $this->record->model_id;

        if (! $type || ! $id) {
            return null;
        }

        $pattern = AuditLogResource::MODEL_RESOURCE_MAP[$type] ?? null;

        if (! $pattern) {
            return null;
        }

        return url(str_replace('{id}', (string) $id, $pattern));
    }
}
