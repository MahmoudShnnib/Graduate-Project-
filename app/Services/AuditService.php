<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    private const ACTION_LABELS = [
        'Grade_updated' => 'تحديث درجة',
        'Application_updated' => 'تحديث طلب التحاق',
        'Student_updated' => 'تحديث بيانات طالب',
        'StudentEnrollment_updated' => 'تحديث تسجيل مقرر',
        'ThesisRegistration_updated' => 'تحديث تسجيل رسالة',
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

    public function log(string $action, Model $model, array $oldValues = [], array $newValues = []): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }

    public static function actionLabel(string $action): string
    {
        return self::ACTION_LABELS[$action] ?? $action;
    }
}
