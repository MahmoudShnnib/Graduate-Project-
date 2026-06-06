<?php

namespace App\Console\Commands;

use App\Channels\WhatsAppMessage;
use App\Models\AddDropRequest;
use App\Models\Application;
use App\Models\ClearanceRequest;
use App\Models\Grade;
use App\Models\Student;
use App\Models\SuspensionRequest;
use App\Models\ThesisRegistration;
use App\Notifications\AddDropDecisionNotification;
use App\Notifications\ApplicationExamPassedNotification;
use App\Notifications\ApplicationReceivedNotification;
use App\Notifications\ClearanceIssuedNotification;
use App\Notifications\GradePublishedNotification;
use App\Notifications\SuspensionDecisionNotification;
use App\Notifications\ThesisDecisionNotification;
use App\Notifications\WelcomeStudentNotification;
use App\Services\WhatsAppOtpService;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class WhatsAppTest extends Command
{
    protected $signature = 'whatsapp:test {phone : Recipient number, e.g. 0912345678}
        {--type=all : otp | application_received | exam_passed | welcome | grade | add_drop | suspension | thesis | clearance | all}';

    protected $description = 'Send a real WhatsApp message (OTP or any notification) to a number to test delivery and wording.';

    public function handle(WhatsAppService $whatsApp, WhatsAppOtpService $otp): int
    {
        if (! $whatsApp->isConfigured()) {
            $this->error('WhatsApp (Qonvo) is not configured — set QONVO_API_TOKEN and QONVO_SENDER_PHONE in .env.');

            return self::FAILURE;
        }

        $phone = $this->argument('phone');
        $type = $this->option('type');

        $stub = (object) ['name' => 'طالب تجريبي', 'email' => 'test@pgsms.test', 'phone' => $phone];

        $messages = $this->buildMessages($stub);

        if ($type === 'otp' || $type === 'all') {
            $result = $otp->request($phone);
            $this->line(($result['sent'] ? '<info>✔</info>' : '<error>✘</error>').' otp — '.$result['message']);
        }

        foreach ($messages as $key => $message) {
            if ($type !== 'all' && $type !== $key) {
                continue;
            }

            if (! $message instanceof WhatsAppMessage) {
                $this->line("<comment>—</comment> {$key} — skipped (no seeded data to build it)");

                continue;
            }

            try {
                $whatsApp->sendText($phone, $message);
                $this->line("<info>✔</info> {$key} — sent");
            } catch (\Throwable $e) {
                $this->line("<error>✘</error> {$key} — ".$e->getMessage());
            }
        }

        $this->newLine();
        $this->info("Done. Check WhatsApp on {$phone}.");

        return self::SUCCESS;
    }

    /**
     * Build a WhatsApp message per notification type using seeded records.
     * Types with no seeded record resolve to null and are reported as skipped.
     *
     * @return array<string, WhatsAppMessage|null>
     */
    private function buildMessages(object $notifiable): array
    {
        $application = Application::first();
        $student = Student::first();
        $grade = Grade::first();
        $addDrop = AddDropRequest::first();
        $suspension = SuspensionRequest::first();
        $thesis = ThesisRegistration::first();
        $clearance = ClearanceRequest::first();

        return [
            'application_received' => $application
                ? (new ApplicationReceivedNotification($application))->toWhatsApp($notifiable) : null,
            'exam_passed' => $application
                ? (new ApplicationExamPassedNotification($application))->toWhatsApp($notifiable) : null,
            'welcome' => $student
                ? (new WelcomeStudentNotification($student))->toWhatsApp($notifiable) : null,
            'grade' => $grade
                ? (new GradePublishedNotification($grade))->toWhatsApp($notifiable) : null,
            'add_drop' => $addDrop
                ? (new AddDropDecisionNotification($addDrop))->toWhatsApp($notifiable) : null,
            'suspension' => $suspension
                ? (new SuspensionDecisionNotification($suspension))->toWhatsApp($notifiable) : null,
            'thesis' => $thesis
                ? (new ThesisDecisionNotification($thesis))->toWhatsApp($notifiable) : null,
            'clearance' => $clearance
                ? (new ClearanceIssuedNotification($clearance))->toWhatsApp($notifiable) : null,
        ];
    }
}
