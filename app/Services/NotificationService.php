<?php

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Models\NotificationLog;
use App\Models\Student;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function notifyStudent(Student $student, string $event, string $message): void
    {
        try {
            if ($student->user?->email) {
                $student->user->notify(new \Illuminate\Notifications\Messages\MailMessage);

                NotificationLog::create([
                    'student_id' => $student->id,
                    'channel' => NotificationChannel::Email,
                    'event' => $event,
                    'message' => $message,
                    'status' => 'sent',
                    'sent_at' => now(),
                    'created_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning("Notification failed for student {$student->id}: {$e->getMessage()}");

            NotificationLog::create([
                'student_id' => $student->id,
                'channel' => NotificationChannel::Email,
                'event' => $event,
                'message' => $message,
                'status' => 'failed',
                'sent_at' => now(),
                'created_at' => now(),
            ]);
        }

        try {
            // WhatsApp stub — log to DB if API key not configured
            NotificationLog::create([
                'student_id' => $student->id,
                'channel' => NotificationChannel::Whatsapp,
                'event' => $event,
                'message' => $message,
                'status' => config('services.whatsapp.api_key') ? 'sent' : 'failed',
                'sent_at' => now(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning("WhatsApp notification failed for student {$student->id}: {$e->getMessage()}");
        }
    }
}
