<?php

namespace App\Observers;

use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    /**
     * Fields whose changes are noise and must never produce an audit entry.
     */
    private const BLOCKED_FIELDS = [
        'updated_at',
        'created_at',
        'password',
        'remember_token',
        'email_verified_at',
        'last_seen_activity_id',
    ];

    public function __construct(
        private AuditService $auditService,
    ) {}

    public function created(Model $model): void
    {
        $this->auditService->log(class_basename($model).'_created', $model);
    }

    public function updated(Model $model): void
    {
        $changed = $this->meaningfulChanges($model);

        if (empty($changed)) {
            return;
        }

        $oldValues = array_intersect_key($model->getOriginal(), $changed);

        $this->auditService->log(
            action: class_basename($model).'_updated',
            model: $model,
            oldValues: $oldValues,
            newValues: $changed,
        );
    }

    public function deleted(Model $model): void
    {
        $this->auditService->log(class_basename($model).'_deleted', $model);
    }

    /**
     * @return array<string, mixed>
     */
    private function meaningfulChanges(Model $model): array
    {
        return array_filter(
            $model->getChanges(),
            fn (string $field): bool => ! in_array($field, self::BLOCKED_FIELDS, true)
                && ! str_ends_with($field, '_token'),
            ARRAY_FILTER_USE_KEY,
        );
    }
}
