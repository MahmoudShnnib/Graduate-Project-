<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Student;
use App\Notifications\ApplicationInitiallyApprovedNotification;
use App\Notifications\GradesPublishedNotification;
use App\Notifications\WelcomeStudentNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function notifyApplicationInitiallyApproved(Application $application): void
    {
        $this->dispatch($application, new ApplicationInitiallyApprovedNotification($application));
    }

    public function notifyWelcomeStudent(Student $student): void
    {
        $this->dispatch($student->user, new WelcomeStudentNotification($student));
    }

    public function notifyGradesPublished(Student $student): void
    {
        $this->dispatch($student->user, new GradesPublishedNotification);
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
