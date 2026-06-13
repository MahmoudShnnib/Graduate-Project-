<?php

namespace App\Services;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Models\AuditLog;
use App\Models\Semester;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditService
{
    /**
     * One id shared by every audit row written during the same operation/request,
     * so a decision and the record edits it triggers can be grouped (and collapsed
     * in the activity bell) without losing any of them in storage.
     */
    private static ?string $batchId = null;

    private const VERB_LABELS = [
        'created' => 'إضافة',
        'updated' => 'تعديل',
        'deleted' => 'حذف',
    ];

    private const ACTION_LABELS = [
        'grade_submitted' => 'تقديم درجات للاعتماد',
        'grade_approved' => 'اعتماد درجة',
        'grades_midterm_published' => 'نشر درجات أعمال الفصل',
        'grades_published' => 'نشر الدرجات النهائية',
        'application_initial_approval' => 'قبول مبدئي لطلب التحاق',
        'application_approved' => 'قبول طلب التحاق',
        'application_rejected' => 'رفض طلب التحاق',
        'exam_passed' => 'نجاح في الامتحان',
        'exam_failed' => 'رسوب في الامتحان',
        'student_enrolled' => 'تسجيل طالب في مقرر',
        'student_dropped' => 'حذف طالب من مقرر',
        'student_status_changed' => 'تغيير حالة طالب',
        'student_graduated' => 'تخريج طالب',
        'course_offering_closed' => 'إغلاق مقرر مطروح',
        'course_offering_opened' => 'فتح مقرر مطروح',
        'add_drop_approved' => 'قبول طلب إضافة/حذف',
        'add_drop_rejected' => 'رفض طلب إضافة/حذف',
        'suspension_approved' => 'قبول طلب إيقاف قيد',
        'suspension_rejected' => 'رفض طلب إيقاف قيد',
        'thesis_printed' => 'طباعة تسجيل رسالة',
        'thesis_approved' => 'قبول تسجيل رسالة',
        'thesis_rejected' => 'رفض تسجيل رسالة',
        'discussion_scheduled' => 'جدولة مناقشة',
        'discussion_rejected' => 'رفض طلب مناقشة',
        'discussion_done' => 'تمت المناقشة',
        'discussion_approved' => 'قبول نتيجة المناقشة',
        'discussion_rejected_post' => 'رفض نتيجة المناقشة',
        'discussion_completed' => 'اكتمال المناقشة',
        'clearance_issued' => 'إصدار استماحة',
        'exceptional_accepted' => 'قبول فرصة استثنائية',
        'exceptional_rejected' => 'رفض فرصة استثنائية',
        'exceptional_summoned' => 'استدعاء طالب',
        'survey_submitted' => 'تقديم استطلاع مقررات',
        'suspension_requested' => 'تقديم طلب إيقاف قيد',
        'thesis_submitted' => 'تقديم طلب تسجيل رسالة',
        'discussion_requested' => 'تقديم طلب مناقشة',
        'add_drop_requested' => 'تقديم طلب إضافة/حذف',
    ];

    /**
     * One short sentence per action so an admin instantly understands what happened.
     */
    private const ACTION_DESCRIPTIONS = [
        'grade_submitted' => 'قام عضو هيئة التدريس برفع درجات المقرر لاعتمادها من الإدارة.',
        'grade_approved' => 'تم اعتماد درجة الطالب بشكل نهائي واحتسابها في المعدل.',
        'grades_midterm_published' => 'تم نشر درجات أعمال الفصل لطلاب المقرر.',
        'grades_published' => 'تم نشر الدرجات النهائية لطلاب المقرر.',
        'application_initial_approval' => 'تم قبول طلب الالتحاق مبدئياً ودعوة المتقدم لامتحان المفاضلة.',
        'application_approved' => 'تم قبول طلب الالتحاق نهائياً وإنشاء حساب الطالب ورقمه الجامعي.',
        'application_rejected' => 'تم رفض طلب الالتحاق.',
        'exam_passed' => 'اجتاز المتقدم امتحان المفاضلة بنجاح.',
        'exam_failed' => 'لم يجتز المتقدم امتحان المفاضلة.',
        'student_enrolled' => 'تم تسجيل الطالب في مقرر دراسي.',
        'student_dropped' => 'تم حذف الطالب من مقرر دراسي.',
        'student_status_changed' => 'تم تغيير الحالة الأكاديمية للطالب.',
        'student_graduated' => 'تم تخريج الطالب.',
        'course_offering_closed' => 'تم إغلاق المقرر المطروح أمام التسجيل.',
        'course_offering_opened' => 'تم فتح المقرر المطروح للتسجيل.',
        'add_drop_approved' => 'تمت الموافقة على طلب الإضافة/الحذف وتعديل تسجيل الطالب.',
        'add_drop_rejected' => 'تم رفض طلب الإضافة/الحذف.',
        'suspension_approved' => 'تمت الموافقة على طلب إيقاف قيد الطالب.',
        'suspension_rejected' => 'تم رفض طلب إيقاف القيد.',
        'thesis_printed' => 'تمت طباعة نموذج تسجيل الرسالة العلمية.',
        'thesis_approved' => 'تمت الموافقة على تسجيل الرسالة العلمية للطالب.',
        'thesis_rejected' => 'تم رفض تسجيل الرسالة العلمية.',
        'discussion_scheduled' => 'تم تحديد موعد مناقشة رسالة الطالب.',
        'discussion_rejected' => 'تم رفض طلب المناقشة.',
        'discussion_done' => 'تمت مناقشة رسالة الطالب.',
        'discussion_approved' => 'تم اعتماد نتيجة المناقشة.',
        'discussion_rejected_post' => 'تم رفض نتيجة المناقشة.',
        'discussion_completed' => 'اكتملت كافة إجراءات المناقشة.',
        'clearance_issued' => 'تم إصدار إخلاء الطرف (الاستماحة) للطالب.',
        'exceptional_accepted' => 'تمت الموافقة على منح الطالب فرصة استثنائية.',
        'exceptional_rejected' => 'تم رفض منح الطالب فرصة استثنائية.',
        'exceptional_summoned' => 'تم استدعاء الطالب بخصوص وضعه الأكاديمي.',
        'survey_submitted' => 'قام الطالب بتقديم استطلاع رغبات المقررات.',
        'suspension_requested' => 'قدّم الطالب طلب إيقاف قيد.',
        'thesis_submitted' => 'قدّم الطالب طلب تسجيل رسالة علمية.',
        'discussion_requested' => 'قدّم الطالب طلب مناقشة.',
        'add_drop_requested' => 'قدّم الطالب طلب إضافة/حذف مقررات.',
    ];

    private const VERB_DESCRIPTIONS = [
        'created' => 'تمت إضافة %s جديد إلى النظام.',
        'updated' => 'تم تعديل بيانات %s.',
        'deleted' => 'تم حذف %s من النظام.',
    ];

    public function log(string $action, Model $model, array $oldValues = [], array $newValues = []): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'batch_id' => self::batchId(),
            'semester_id' => Semester::activeId(),
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'subject_label' => self::subjectLabel($model),
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }

    /**
     * The shared correlation id for the current operation (memoized per request/process).
     */
    public static function batchId(): string
    {
        return self::$batchId ??= (string) Str::uuid();
    }

    /**
     * Start a fresh batch. MUST be called at the boundary of every unit of work
     * (request / queued job / console command) so separate operations never share an
     * id — critical under long-lived runtimes (Octane, queue workers) where the static
     * would otherwise persist and glue unrelated actions into one fake operation.
     */
    public static function resetBatch(): void
    {
        self::$batchId = null;
    }

    /**
     * Move a semester's audit logs into cold storage (audit_log_archives) and remove them
     * from the live table. Preserves the full trail while keeping the working table lean.
     * Called when a semester is archived.
     */
    public static function archiveSemesterLogs(int $semesterId): int
    {
        $moved = 0;

        \DB::table('audit_logs')
            ->where('semester_id', $semesterId)
            ->orderBy('id')
            ->chunkById(500, function ($rows) use (&$moved): void {
                \DB::table('audit_log_archives')->insert(
                    collect($rows)->map(fn ($row): array => [
                        'original_id' => $row->id,
                        'batch_id' => $row->batch_id,
                        'semester_id' => $row->semester_id,
                        'user_id' => $row->user_id,
                        'action' => $row->action,
                        'model_type' => $row->model_type,
                        'model_id' => $row->model_id,
                        'subject_label' => $row->subject_label,
                        'old_values' => $row->old_values,
                        'new_values' => $row->new_values,
                        'ip_address' => $row->ip_address,
                        'created_at' => $row->created_at,
                        'archived_at' => now(),
                    ])->all()
                );

                $moved += count($rows);
            });

        \DB::table('audit_logs')->where('semester_id', $semesterId)->delete();

        return $moved;
    }

    /**
     * A human label for the audited record, captured at log time so deletions
     * still read meaningfully after the row is gone.
     */
    public static function subjectLabel(Model $model): ?string
    {
        if (method_exists($model, 'auditSubjectLabel')) {
            $label = $model->auditSubjectLabel();

            if (! empty($label)) {
                return $label;
            }
        }

        foreach (['name', 'name_ar', 'title', 'enrollment_no'] as $attribute) {
            $value = $model->getAttribute($attribute);

            if (! empty($value)) {
                return (string) $value;
            }
        }

        return $model->getKey() ? '#'.$model->getKey() : null;
    }

    public static function actionLabel(string $action): string
    {
        if (isset(self::ACTION_LABELS[$action])) {
            return self::ACTION_LABELS[$action];
        }

        if (preg_match('/^(.+)_(created|updated|deleted)$/', $action, $matches)) {
            $noun = AuditLogResource::MODEL_LABELS['App\\Models\\'.$matches[1]] ?? $matches[1];

            return self::VERB_LABELS[$matches[2]].' '.$noun;
        }

        return $action;
    }

    /**
     * A brief, admin-friendly explanation of what an action means.
     */
    public static function actionDescription(string $action): string
    {
        if (isset(self::ACTION_DESCRIPTIONS[$action])) {
            return self::ACTION_DESCRIPTIONS[$action];
        }

        if (preg_match('/^(.+)_(created|updated|deleted)$/', $action, $matches)) {
            $noun = AuditLogResource::MODEL_LABELS['App\\Models\\'.$matches[1]] ?? $matches[1];

            return sprintf(self::VERB_DESCRIPTIONS[$matches[2]], $noun);
        }

        return '';
    }
}
