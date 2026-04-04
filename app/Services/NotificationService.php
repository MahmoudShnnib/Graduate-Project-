<?php

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Models\AddDropRequest;
use App\Models\Application;
use App\Models\ClearanceRequest;
use App\Models\Grade;
use App\Models\NotificationLog;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\SuspensionRequest;
use App\Models\ThesisRegistration;
use App\Notifications\AddDropDecisionNotification;
use App\Notifications\ApplicationReceivedNotification;
use App\Notifications\ApplicationStatusChangedNotification;
use App\Notifications\ClearanceIssuedNotification;
use App\Notifications\EnrollmentConfirmedNotification;
use App\Notifications\GradePublishedNotification;
use App\Notifications\SuspensionDecisionNotification;
use App\Notifications\ThesisDecisionNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function notifyApplicationReceived(Application $application): void
    {
        $this->dispatch(
            $application->user,
            new ApplicationReceivedNotification($application),
            $application->user?->student,
            'application_received',
            'تم استلام طلب القبول بنجاح'
        );
    }

    public function notifyApplicationStatusChanged(Application $application): void
    {
        $this->dispatch(
            $application->user,
            new ApplicationStatusChangedNotification($application),
            $application->user?->student,
            'application_status_changed',
            'تم تحديث حالة طلب القبول'
        );
    }

    public function notifyGradePublished(Grade $grade): void
    {
        $student = $grade->enrollment?->student;

        if (! $student) {
            return;
        }

        $this->dispatch(
            $student->user,
            new GradePublishedNotification($grade),
            $student,
            'grade_published',
            'تم اعتماد درجاتك في مقرر'
        );
    }

    public function notifyThesisDecision(ThesisRegistration $registration): void
    {
        $student = $registration->student;

        $this->dispatch(
            $student?->user,
            new ThesisDecisionNotification($registration),
            $student,
            'thesis_decision',
            $registration->status === 'approved' ? 'تم قبول تسجيل رسالتك' : 'تم رفض تسجيل رسالتك'
        );
    }

    public function notifySuspensionDecision(SuspensionRequest $request): void
    {
        $student = $request->student;

        $this->dispatch(
            $student?->user,
            new SuspensionDecisionNotification($request),
            $student,
            'suspension_decision',
            $request->status?->value === 'approved' ? 'تم قبول طلب الانقطاع' : 'تم رفض طلب الانقطاع'
        );
    }

    public function notifyAddDropDecision(AddDropRequest $request): void
    {
        $student = $request->student;

        $this->dispatch(
            $student?->user,
            new AddDropDecisionNotification($request),
            $student,
            'add_drop_decision',
            'تم البت في طلب الإضافة/الحذف'
        );
    }

    public function notifyClearanceIssued(ClearanceRequest $clearance): void
    {
        $student = $clearance->student;

        $this->dispatch(
            $student?->user,
            new ClearanceIssuedNotification($clearance),
            $student,
            'clearance_issued',
            'تم اعتماد استماحتك'
        );
    }

    public function notifyEnrollmentConfirmed(StudentEnrollment $enrollment): void
    {
        $student = $enrollment->student;

        $this->dispatch(
            $student?->user,
            new EnrollmentConfirmedNotification($enrollment),
            $student,
            'enrollment_confirmed',
            'تم تأكيد تسجيلك في مقرر'
        );
    }

    /**
     * Legacy compatibility — used by existing admin resource actions.
     */
    public function notifyStudent(Student $student, string $event, string $message): void
    {
        $this->logChannel($student, NotificationChannel::Email, $event, $message, 'sent');
        $this->logChannel($student, NotificationChannel::Whatsapp, $event, $message, 'failed');
    }

    private function dispatch(?object $notifiable, Notification $notification, ?Student $student, string $event, string $message): void
    {
        if (! $notifiable || ! $student) {
            return;
        }

        try {
            $notifiable->notify($notification);
            $this->logChannel($student, NotificationChannel::Email, $event, $message, 'sent');
        } catch (\Throwable $e) {
            Log::warning("Notification {$event} failed for student {$student->id}: {$e->getMessage()}");
            $this->logChannel($student, NotificationChannel::Email, $event, $message, 'failed');
        }

        $this->logChannel(
            $student,
            NotificationChannel::Whatsapp,
            $event,
            $message,
            config('services.whatsapp.api_key') ? 'sent' : 'failed'
        );
    }

    private function logChannel(Student $student, NotificationChannel $channel, string $event, string $message, string $status): void
    {
        try {
            NotificationLog::create([
                'student_id' => $student->id,
                'channel' => $channel,
                'event' => $event,
                'message' => $message,
                'status' => $status,
                'sent_at' => now(),
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // Logging must never interrupt the main flow
        }
    }
}
