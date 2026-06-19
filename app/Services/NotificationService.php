<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Student;
use App\Notifications\ApplicationInitiallyApprovedNotification;
use App\Notifications\GradesPublishedNotification;
use App\Notifications\WelcomeStudentNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NotificationService
{
    /**
     * When set, every notification dispatched shares this batch id so a broadcast
     * (e.g. publishing grades to all students) groups into one entry in the logs.
     */
    private ?string $currentBatchId = null;

    /**
     * Run a broadcast inside a single batch so all its rows share one batch id.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function batch(callable $callback): mixed
    {
        $previous = $this->currentBatchId;
        $this->currentBatchId = (string) Str::uuid();

        try {
            return $callback();
        } finally {
            $this->currentBatchId = $previous;
        }
    }

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

        // Only a broadcast (inside batch()) gets a batch id → it groups in the logs.
        // A single send stays ungrouped (batch_id null) and shows as a plain row.
        if ($this->currentBatchId !== null && property_exists($notification, 'batchId')) {
            $notification->batchId ??= $this->currentBatchId;
        }

        try {
            $notifiable->notify($notification);
        } catch (\Throwable $e) {
            $event = $notification->logEvent ?? class_basename($notification);
            Log::warning("Notification {$event} failed: {$e->getMessage()}");
        }
    }
}
