<?php

namespace App\Filament\Pages;

use App\Enums\ExceptionalRequestStatus;
use App\Enums\StudentStatus;
use App\Enums\WarningType;
use App\Models\ExceptionalRequest;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentWarning;
use App\Services\WarningService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ViewExceptionalStudent extends Page
{
    protected string $view = 'filament.pages.view-exceptional-student';

    protected static ?string $slug = 'exceptional-opportunities-page/{student}';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'تفاصيل الطالب';

    public Student $record;

    public function mount(int $student): void
    {
        $this->record = Student::with([
            'user',
            'specialization',
            'dismissalCourse',
            'activeWarnings.course',
            'activeWarnings.semester',
            'exceptionalRequests' => fn ($q) => $q->latest(),
        ])->findOrFail($student);
    }

    public function getHeading(): string
    {
        return $this->record->user?->name ?? 'تفاصيل الطالب';
    }

    public function getDismissalReasonText(): string
    {
        return match ($this->record->dismissal_type) {
            WarningType::CourseFailure => 'رسوب في مادة: '.($this->record->dismissalCourse?->name ?? 'غير محددة'),
            WarningType::ConsecutiveInactive => 'انقطاع متتالي عن الدراسة',
            default => 'غير محدد',
        };
    }

    protected function getHeaderActions(): array
    {
        $latestRequest = $this->record->exceptionalRequests->first();
        $hasPendingOrSummoned = $latestRequest && in_array($latestRequest->status->value, ['pending', 'summoned']);
        $alreadySummoned = $latestRequest && $latestRequest->summon_message;

        return [
            $this->acceptAction($latestRequest, $hasPendingOrSummoned),
            $this->rejectAction($latestRequest, $hasPendingOrSummoned),
            $this->summonAction($latestRequest, $hasPendingOrSummoned, $alreadySummoned),
        ];
    }

    private function acceptAction(?ExceptionalRequest $request, bool $visible): Action
    {
        return Action::make('accept')
            ->label('قبول')
            ->icon(Heroicon::Check)
            ->color('success')
            ->visible($visible && $request)
            ->requiresConfirmation()
            ->modalHeading('قبول الطلب الاستثنائي')
            ->modalDescription('سيتم إعادة الطالب إلى الحالة النشطة. هل أنت متأكد؟')
            ->modalSubmitActionLabel('تأكيد القبول')
            ->action(function () use ($request) {
                $request = ExceptionalRequest::with('student.dismissalCourse')->findOrFail($request->id);
                $student = $request->student;

                $request->update([
                    'status' => ExceptionalRequestStatus::Accepted,
                    'decided_by' => auth()->id(),
                    'decided_at' => now(),
                ]);

                if ($student->dismissal_type === WarningType::CourseFailure && $student->dismissal_course_id) {
                    StudentWarning::where('student_id', $student->id)
                        ->where('type', WarningType::CourseFailure)
                        ->where('course_id', $student->dismissal_course_id)
                        ->where('is_active', true)
                        ->update(['is_active' => false, 'resolved_at' => now()]);

                    $semester = Semester::where('is_active', true)->first();

                    StudentWarning::create([
                        'student_id' => $student->id,
                        'type' => WarningType::CourseFailure,
                        'course_id' => $student->dismissal_course_id,
                        'semester_id' => $semester?->id ?? $student->warnings()->latest()->first()?->semester_id,
                        'has_exceptional_chance' => true,
                    ]);
                }

                if ($student->dismissal_type === WarningType::ConsecutiveInactive) {
                    app(WarningService::class)->clearInactiveWarnings($student);
                }

                $student->update([
                    'status' => StudentStatus::Active,
                    'dismissed_at' => null,
                    'dismissal_type' => null,
                    'dismissal_course_id' => null,
                ]);

                app(WarningService::class)->syncWarningsCount($student);

                Notification::make()
                    ->title('تم قبول الطلب الاستثنائي')
                    ->body('تم إعادة الطالب '.$student->user->name.' إلى الحالة النشطة.')
                    ->success()
                    ->send();

                $this->redirect(ExceptionalOpportunitiesPage::getUrl());
            });
    }

    private function rejectAction(?ExceptionalRequest $request, bool $visible): Action
    {
        return Action::make('reject')
            ->label('رفض')
            ->icon(Heroicon::XMark)
            ->color('danger')
            ->visible($visible && $request)
            ->requiresConfirmation()
            ->modalHeading('رفض الطلب الاستثنائي')
            ->modalDescription('هل أنت متأكد من رفض هذا الطلب؟')
            ->modalSubmitActionLabel('تأكيد الرفض')
            ->action(function () use ($request) {
                $request = ExceptionalRequest::findOrFail($request->id);

                $request->update([
                    'status' => ExceptionalRequestStatus::Rejected,
                    'decided_by' => auth()->id(),
                    'decided_at' => now(),
                ]);

                Notification::make()
                    ->title('تم رفض الطلب الاستثنائي')
                    ->danger()
                    ->send();

                $this->redirect(ExceptionalOpportunitiesPage::getUrl());
            });
    }

    private function summonAction(?ExceptionalRequest $request, bool $visible, bool $alreadySummoned): Action
    {
        return Action::make('summon')
            ->label('استدعاء')
            ->icon(Heroicon::OutlinedMegaphone)
            ->color('info')
            ->visible($visible && $request && ! $alreadySummoned)
            ->requiresConfirmation()
            ->modalHeading('استدعاء الطالب')
            ->modalDescription('اكتب رسالة الاستدعاء التي ستظهر للطالب على لوحة التحكم الخاصة به.')
            ->modalSubmitActionLabel('إرسال الاستدعاء')
            ->form([
                Textarea::make('summon_message')
                    ->label('رسالة الاستدعاء')
                    ->required()
                    ->rows(4)
                    ->placeholder('اكتب رسالة الاستدعاء هنا...'),
            ])
            ->action(function (array $data) use ($request) {
                $request = ExceptionalRequest::findOrFail($request->id);

                $request->update([
                    'status' => ExceptionalRequestStatus::Summoned,
                    'summon_message' => $data['summon_message'],
                    'summon_sent_at' => now(),
                ]);

                Notification::make()
                    ->title('تم إرسال الاستدعاء')
                    ->success()
                    ->send();

                $this->record->refresh();
                $this->record->load(['exceptionalRequests' => fn ($q) => $q->latest()]);
            });
    }
}
