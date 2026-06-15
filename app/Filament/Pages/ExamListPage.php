<?php

namespace App\Filament\Pages;

use App\Actions\ApproveApplication;
use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Semester;
use App\Models\Specialization;
use App\Services\AuditService;
use Filament\Actions\Action;
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

    protected static string|\UnitEnum|null $navigationGroup = 'القبول والتسجيل';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->user()?->role !== 'faculty';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->role !== 'faculty';
    }

    /** @var array<int, string> */
    public array $selected = [];

    /** @var array<int, float|null> */
    public array $scores = [];

    public ?int $activeSpecialization = null;

    public ?int $selectedSemester = null;

    public function mount(): void
    {
        $this->selectedSemester = Semester::where('is_active', true)->value('id');
    }

    public function getSpecializations(): Collection
    {
        return Specialization::orderBy('name')->get();
    }

    public function getSemesters(): Collection
    {
        return Semester::orderByDesc('is_active')->orderByDesc('year')->get();
    }

    public function filterBySpecialization(?int $id): void
    {
        $this->activeSpecialization = $this->activeSpecialization === $id ? null : $id;
    }

    public function getApplications(): Collection
    {
        return Application::where('status', ApplicationStatus::InitiallyApproved)
            ->with('specialization')
            ->when($this->selectedSemester, fn ($q) => $q->where('semester_id', $this->selectedSemester))
            ->when($this->activeSpecialization, fn ($q) => $q->where('specialization_id', $this->activeSpecialization))
            ->orderBy('exam_date')
            ->get();
    }

    public function toggleSelectAll(bool $checked): void
    {
        $this->selected = $checked
            ? $this->getApplications()->pluck('id')->map(fn ($id): string => (string) $id)->all()
            : [];
    }

    public function markPassedAction(): Action
    {
        return Action::make('markPassed')
            ->label('مقبول')
            ->icon(Heroicon::OutlinedCheck)
            ->color('success')
            ->size('sm')
            ->disabled(fn (): bool => empty($this->selected))
            ->requiresConfirmation()
            ->modalIcon(Heroicon::OutlinedCheckBadge)
            ->modalHeading('قبول الطلاب المحددين')
            ->modalDescription('سيتم قبول الطلاب المحددين نهائياً وإنشاء حساباتهم الطلابية وإشعارهم. لا يمكن التراجع عن هذا الإجراء.')
            ->modalSubmitActionLabel('قبول نهائي')
            ->action(fn () => $this->markPassed());
    }

    public function markFailedAction(): Action
    {
        return Action::make('markFailed')
            ->label('مرفوض')
            ->icon(Heroicon::OutlinedXMark)
            ->color('danger')
            ->size('sm')
            ->disabled(fn (): bool => empty($this->selected))
            ->requiresConfirmation()
            ->modalHeading('رفض الطلاب المحددين')
            ->modalDescription('سيتم تسجيل رسوب الطلاب المحددين في الامتحان التفاضلي ورفض طلباتهم.')
            ->modalSubmitActionLabel('تأكيد الرفض')
            ->action(fn () => $this->markFailed());
    }

    public function markPassed(): void
    {
        if (empty($this->selected)) {
            Notification::make()->title('الرجاء تحديد طالب واحد على الأقل')->warning()->send();

            return;
        }

        $approve = app(ApproveApplication::class);
        $audit = app(AuditService::class);
        $successCount = 0;
        $errors = [];

        $applications = Application::whereIn('id', $this->selected)
            ->where('status', ApplicationStatus::InitiallyApproved)
            ->get();

        foreach ($applications as $application) {
            AuditService::resetBatch();

            try {
                $application->update([
                    'exam_score' => $this->scores[$application->id] ?? null,
                    'exam_passed' => true,
                    'exam_result_date' => now(),
                ]);

                $approve->handle($application);
                $audit->log('exam_passed', $application, newValues: ['exam_score' => $application->exam_score]);
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

        $audit = app(AuditService::class);

        $applications = Application::whereIn('id', $this->selected)
            ->where('status', ApplicationStatus::InitiallyApproved)
            ->get();

        foreach ($applications as $application) {
            AuditService::resetBatch();

            $application->update([
                'status' => ApplicationStatus::Rejected,
                'rejection_reason' => 'لم يجتز الامتحان التفاضلي',
                'exam_score' => $this->scores[$application->id] ?? null,
                'exam_passed' => false,
                'exam_result_date' => now(),
            ]);

            $audit->log('exam_failed', $application, newValues: ['exam_score' => $application->exam_score]);
        }

        $this->selected = [];
        $this->scores = [];

        Notification::make()
            ->title("تم رفض {$applications->count()} طالب")
            ->success()
            ->send();
    }
}
