<?php

namespace App\Filament\Pages;

use App\Actions\ApproveApplication;
use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Services\NotificationService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class ExamListPage extends Page
{
    protected string $view = 'filament.pages.exam-list';

    protected static ?string $navigationLabel = 'قائمة الامتحانات';

    protected static ?string $title = 'قائمة الامتحانات التفاضلية';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?int $navigationSort = 3;

    /** @var array<int, string> */
    public array $selected = [];

    /** @var array<int, float|null> */
    public array $scores = [];

    public function getApplications(): Collection
    {
        return Application::where('status', ApplicationStatus::InitiallyApproved)
            ->with('specialization')
            ->orderBy('exam_date')
            ->get();
    }

    public function markPassed(): void
    {
        if (empty($this->selected)) {
            Notification::make()->title('الرجاء تحديد طالب واحد على الأقل')->warning()->send();

            return;
        }

        $approve = app(ApproveApplication::class);
        $notifier = app(NotificationService::class);
        $successCount = 0;
        $errors = [];

        $applications = Application::whereIn('id', $this->selected)
            ->where('status', ApplicationStatus::InitiallyApproved)
            ->get();

        foreach ($applications as $application) {
            try {
                $application->update([
                    'exam_score' => $this->scores[$application->id] ?? null,
                    'exam_passed' => true,
                    'exam_result_date' => now(),
                ]);

                $approve->handle($application);
                $notifier->notifyApplicationExamPassed($application);
                $successCount++;
            } catch (\Throwable $e) {
                $errors[] = $application->name.': '.$e->getMessage();
            }
        }

        $this->selected = [];
        $this->scores = [];

        if ($successCount > 0) {
            Notification::make()
                ->title("تم قبول {$successCount} طالب بنجاح")
                ->success()
                ->send();
        }

        if (! empty($errors)) {
            Notification::make()
                ->title('حدثت أخطاء')
                ->body(implode("\n", $errors))
                ->danger()
                ->send();
        }
    }

    public function markFailed(): void
    {
        if (empty($this->selected)) {
            Notification::make()->title('الرجاء تحديد طالب واحد على الأقل')->warning()->send();

            return;
        }

        $notifier = app(NotificationService::class);

        $applications = Application::whereIn('id', $this->selected)
            ->where('status', ApplicationStatus::InitiallyApproved)
            ->get();

        foreach ($applications as $application) {
            $application->update([
                'status' => ApplicationStatus::Rejected,
                'rejection_reason' => 'لم يجتز الامتحان التفاضلي',
                'exam_score' => $this->scores[$application->id] ?? null,
                'exam_passed' => false,
                'exam_result_date' => now(),
            ]);

            $notifier->notifyApplicationStatusChanged($application);
        }

        $this->selected = [];
        $this->scores = [];

        Notification::make()
            ->title("تم رفض {$applications->count()} طالب")
            ->success()
            ->send();
    }
}
