<?php

namespace App\Observers;

use App\Models\Application;
use App\Models\Grade;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\ThesisRegistration;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    private const WATCHED_FIELDS = [
        Grade::class => ['coursework_grade', 'final_grade', 'status', 'midterm_published_at', 'published_at'],
        Application::class => ['status', 'rejection_reason'],
        Student::class => ['warnings_count', 'status', 'enrollment_no'],
        StudentEnrollment::class => ['status'],
        ThesisRegistration::class => ['status'],
    ];

    public function __construct(
        private AuditService $auditService,
    ) {}

    public function updated(Model $model): void
    {
        $watchedFields = self::WATCHED_FIELDS[get_class($model)] ?? [];

        $changedFields = array_intersect_key(
            $model->getChanges(),
            array_flip($watchedFields)
        );

        if (empty($changedFields)) {
            return;
        }

        $oldValues = array_intersect_key(
            $model->getOriginal(),
            $changedFields
        );

        $this->auditService->log(
            action: class_basename($model).'_updated',
            model: $model,
            oldValues: $oldValues,
            newValues: $changedFields,
        );
    }
}
