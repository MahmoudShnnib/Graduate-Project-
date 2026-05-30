<?php

namespace App\Filament\Resources\Graduates\Pages;

use App\Enums\StudentStatus;
use App\Filament\Resources\Graduates\GraduateResource;
use App\Models\DiscussionRequest;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewGraduate extends ViewRecord
{
    protected static string $resource = GraduateResource::class;

    protected string $view = 'filament.resources.graduates.pages.view-graduate';

    public function getTitle(): string
    {
        return 'خريج — '.$this->record->student->user->name;
    }

    protected function resolveRecord(int|string $key): DiscussionRequest
    {
        return DiscussionRequest::with([
            'student.user',
            'student.specialization',
            'thesisRegistration.supervisor',
            'thesisRegistration.facultySupervisor',
        ])->findOrFail($key);
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->markGraduatedAction(),
            $this->printDegreeAction(),
        ];
    }

    private function markGraduatedAction(): Action
    {
        return Action::make('mark_graduated')
            ->label('تم الاستلام لاستماحة')
            ->icon(Heroicon::OutlinedAcademicCap)
            ->color('success')
            ->visible(fn () => $this->record->student->status !== StudentStatus::Graduated)
            ->requiresConfirmation()
            ->modalHeading('تأكيد التخريج')
            ->modalDescription('سيتم تغيير حالة الطالب إلى "متخرج". هل أنت متأكد؟')
            ->modalSubmitActionLabel('تأكيد')
            ->action(function () {
                $this->record->student->update([
                    'status' => StudentStatus::Graduated,
                ]);

                Notification::make()
                    ->title('تم تخريج الطالب بنجاح')
                    ->success()
                    ->send();
            });
    }

    private function printDegreeAction(): Action
    {
        return Action::make('print_degree')
            ->label('طباعة الافادة')
            ->icon(Heroicon::OutlinedPrinter)
            ->color('info')
            ->visible(fn () => $this->record->student->status === StudentStatus::Graduated)
            ->url(fn () => route('print.graduation-certificate', $this->record))
            ->openUrlInNewTab();
    }

    public function getCoursesCompletionDate(): ?string
    {
        $student = $this->record->student;

        $lastSemester = $student->enrollments()
            ->whereHas('grade', fn ($q) => $q->where('status', 'approved'))
            ->join('course_offerings', 'student_enrollments.course_offering_id', '=', 'course_offerings.id')
            ->join('semesters', 'course_offerings.semester_id', '=', 'semesters.id')
            ->orderByDesc('semesters.end_date')
            ->select('semesters.end_date')
            ->first();

        return $lastSemester?->end_date;
    }

    public function getSupervisorDetails(): ?array
    {
        $reg = $this->record->thesisRegistration;

        if ($reg->is_external_supervisor && $reg->supervisor) {
            return [
                'type' => 'خارجي',
                'name' => $reg->supervisor->name,
                'academic_rank' => $reg->supervisor->academic_rank,
                'institution' => $reg->supervisor->institution,
                'email' => $reg->supervisor->email,
            ];
        }

        if (! $reg->is_external_supervisor && $reg->facultySupervisor) {
            return [
                'type' => 'من الجامعة',
                'name' => $reg->facultySupervisor->name,
                'email' => $reg->facultySupervisor->email,
            ];
        }

        return null;
    }
}
