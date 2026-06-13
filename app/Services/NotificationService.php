<?php

namespace App\Services;

use App\Models\AddDropRequest;
use App\Models\Application;
use App\Models\ClearanceRequest;
use App\Models\Grade;
use App\Models\Student;
use App\Models\SuspensionRequest;
use App\Models\ThesisRegistration;
use App\Notifications\AddDropDecisionNotification;
use App\Notifications\ApplicationExamPassedNotification;
use App\Notifications\ApplicationInitiallyApprovedNotification;
use App\Notifications\ApplicationReceivedNotification;
use App\Notifications\ClearanceIssuedNotification;
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
        $this->dispatch($application, new ApplicationReceivedNotification($application));
    }

    public function notifyApplicationInitiallyApproved(Application $application): void
    {
        $this->dispatch($application, new ApplicationInitiallyApprovedNotification($application));
    }

    public function notifyApplicationExamPassed(Application $application): void
    {
        $this->dispatch($application, new ApplicationExamPassedNotification($application));
    }

    public function notifyWelcomeStudent(Student $student, ?string $plainPassword = null): void
    {
        $this->dispatch($student->user, new WelcomeStudentNotification($student, $plainPassword));
    }

    public function notifyGradePublished(Grade $grade): void
    {
        $student = $grade->enrollment?->student;

        if (! $student) {
            return;
        }

        $this->dispatch($student->user, new GradePublishedNotification($grade));
    }

    public function notifyThesisDecision(ThesisRegistration $registration): void
    {
        $this->dispatch($registration->student?->user, new ThesisDecisionNotification($registration));
    }

    public function notifySuspensionDecision(SuspensionRequest $request): void
    {
        $this->dispatch($request->student?->user, new SuspensionDecisionNotification($request));
    }

    public function notifyAddDropDecision(AddDropRequest $request): void
    {
        $this->dispatch($request->student?->user, new AddDropDecisionNotification($request));
    }

    public function notifyClearanceIssued(ClearanceRequest $clearance): void
    {
        $this->dispatch($clearance->student?->user, new ClearanceIssuedNotification($clearance));
    }

    private function dispatch(?object $notifiable, Notification $notification): void
    {
        if (! $notifiable) {
            return;
        }

        try {
            $notifiable->notify($notification);
        } catch (\Throwable $e) {
            $event = $notification->logEvent ?? class_basename($notification);
            Log::warning("Notification {$event} failed: {$e->getMessage()}");
        }
    }
}
