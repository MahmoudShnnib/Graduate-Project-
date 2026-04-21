<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Services\GradeCalculationService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalHeading('حذف الطالب')
                ->modalDescription('سيتم حذف سجل الطالب وحساب المستخدم وطلب التسجيل المرتبط به. هل أنت متأكد؟')
                ->after(function ($record) {
                    $record->user?->delete();
                }),
        ];
    }

    /**
     * Populate the hidden transfer_courses form field from existing TransferCourse
     * rows so the "معادلة مواد" modal opens with the student's current entries.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['transfer_courses'] = $this->record->transferCourses()
            ->get(['course_id', 'coursework_grade', 'final_grade'])
            ->map(fn ($tc): array => [
                'course_id' => $tc->course_id,
                'coursework_grade' => $tc->coursework_grade,
                'final_grade' => $tc->final_grade,
            ])
            ->toArray();

        return $data;
    }

    /**
     * Persist transfer_courses form data to the TransferCourse table and
     * trigger a GPA recalculation. Mirrors the create-side logic in
     * CreateStudent::handleRecordCreation.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $transferCourses = $data['transfer_courses'] ?? [];
        $isTransfer = (bool) ($data['is_transfer'] ?? false);

        // Strip from $data — not a Student column.
        unset($data['transfer_courses']);

        $record = parent::handleRecordUpdate($record, $data);

        // Replace existing transfer courses with the new set.
        // delete() + create() is simplest for rich-data repeaters; matches the
        // create path pattern.
        $record->transferCourses()->delete();

        if ($isTransfer && ! empty($transferCourses)) {
            foreach ($transferCourses as $courseData) {
                $record->transferCourses()->create([
                    'course_id' => $courseData['course_id'],
                    'coursework_grade' => $courseData['coursework_grade'],
                    'final_grade' => $courseData['final_grade'],
                ]);
            }
        }

        // Recalculate GPA — transfer courses count toward it per
        // GradeCalculationService. Suspended students are skipped inside the service.
        app(GradeCalculationService::class)->recalculateStudentGPA($record);

        return $record;
    }
}
