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
use App\Notifications\ApplicationExamPassedNotification;
use App\Notifications\ApplicationReceivedNotification;
use App\Notifications\ApplicationStatusChangedNotification;
use App\Notifications\ClearanceIssuedNotification;
use App\Notifications\EnrollmentConfirmedNotification;
use App\Notifications\GradePublishedNotification;
use App\Notifications\SuspensionDecisionNotification;
use App\Notifications\ThesisDecisionNotification;
use App\Notifications\WelcomeStudentNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function notifyApplicationReceived(Application $application): void
    {
        $this->dispatch(
            $application,
            new ApplicationReceivedNotification($application),
            $application->user?->student,
            'application_received',
            'تم استلام طلب القبول بنجاح'
        );
    }

    public function notifyApplicationExamPassed(Application $application): void
    {
        $this->dispatch(
            $application,
            new ApplicationExamPassedNotification($application),
            $application->user?->student,
            'application_exam_passed',
            'اجتزت اختبار القبول — تم قبولك نهائياً'
        );
    }

    public function notifyApplicationStatusChanged(Application $application): void
    {
        $this->dispatch(
            $application,
            new ApplicationStatusChangedNotification($application),
            $application->user?->student,
            'application_status_changed',
            'تم تحديث حالة طلب القبول'
        );
    }

    public function notifyWelcomeStudent(Student $student, ?string $plainPassword = null): void
    {
        $this->dispatch(
            $student->user,
            new WelcomeStudentNotification($student, $plainPassword),
            $student,
            'welcome_student',
            'مرحباً بك في برنامج الدراسات العليا'
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

    private function dispatch(?object $notifiable, Notification $notification, ?Student $student, string $event, string $message): void
    {
        if (! $notifiable) {
            return;
        }

        $emailStatus = 'failed';
        $whatsappStatus = 'skipped';

        try {
            $notifiable->notify($notification);
            $emailStatus = 'sent';

            if (! empty($notifiable->phone) && ! empty(config('services.qonvo.api_token'))) {
                $whatsappStatus = 'sent';
            }
        } catch (\Throwable $e) {
            Log::warning("Notification {$event} failed: {$e->getMessage()}");
        }

        if ($student) {
            $this->logChannel($student, NotificationChannel::Email, $event, $message, $emailStatus);

            if ($whatsappStatus !== 'skipped') {
                $this->logChannel($student, NotificationChannel::Whatsapp, $event, $message, $whatsappStatus);
            }
        }
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
