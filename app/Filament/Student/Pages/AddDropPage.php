<?php

namespace App\Filament\Student\Pages;

use App\Enums\AddDropType;
use App\Models\AddDropRequest;
use App\Models\CourseOffering;
use App\Models\Semester;
use App\Models\StudentEnrollment;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class AddDropPage extends Page
{
    protected string $view = 'filament.student.pages.add-drop';

    protected static ?string $navigationLabel = 'الإضافة والحذف';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string|\UnitEnum|null $navigationGroup = 'الأكاديمي';

    protected static ?int $navigationSort = 5;

    public ?int $addOfferingId = null;

    public string $dropReason = '';

    public string $addReason = '';

    public function getActiveSemester(): ?Semester
    {
        return Semester::where('is_active', true)->first();
    }

    public function isAddDropOpen(): bool
    {
        $semester = $this->getActiveSemester();

        return $semester?->isAddDropOpen() ?? false;
    }

    public function getEnrollments(): Collection
    {
        $student = auth()->user()->student;
        $activeSemester = $this->getActiveSemester();

        if (! $student || ! $activeSemester) {
            return collect();
        }

        return StudentEnrollment::where('student_id', $student->id)
            ->whereHas('courseOffering', fn ($q) => $q->where('semester_id', $activeSemester->id))
            ->with(['courseOffering.course'])
            ->get();
    }

    public function getAvailableOfferings(): Collection
    {
        $student = auth()->user()->student;
        $activeSemester = $this->getActiveSemester();

        if (! $student || ! $activeSemester) {
            return collect();
        }

        $enrolledOfferingIds = StudentEnrollment::where('student_id', $student->id)
            ->whereHas('courseOffering', fn ($q) => $q->where('semester_id', $activeSemester->id))
            ->pluck('course_offering_id');

        return CourseOffering::where('semester_id', $activeSemester->id)
            ->where('is_open', true)
            ->whereNotIn('id', $enrolledOfferingIds)
            ->with('course')
            ->get();
    }

    public function requestDrop(int $enrollmentId): void
    {
        if (! $this->isAddDropOpen()) {
            Notification::make()->title('فترة الإضافة والحذف مغلقة')->danger()->send();

            return;
        }

        $enrollment = StudentEnrollment::findOrFail($enrollmentId);
        $student = auth()->user()->student;

        if ($enrollment->student_id !== $student->id) {
            abort(403);
        }

        AddDropRequest::create([
            'student_id' => $student->id,
            'course_offering_id' => $enrollment->course_offering_id,
            'type' => AddDropType::Drop,
            'status' => 'pending',
            'reason' => $this->dropReason ?: null,
        ]);

        Notification::make()->title('تم إرسال طلب الحذف للمراجعة')->success()->send();
    }

    public function requestAdd(): void
    {
        if (! $this->isAddDropOpen()) {
            Notification::make()->title('فترة الإضافة والحذف مغلقة')->danger()->send();

            return;
        }

        if (! $this->addOfferingId) {
            Notification::make()->title('يرجى اختيار مقرر')->warning()->send();

            return;
        }

        $student = auth()->user()->student;

        AddDropRequest::create([
            'student_id' => $student->id,
            'course_offering_id' => $this->addOfferingId,
            'type' => AddDropType::Add,
            'status' => 'pending',
            'reason' => $this->addReason ?: null,
        ]);

        $this->addOfferingId = null;
        $this->addReason = '';

        Notification::make()->title('تم إرسال طلب الإضافة للمراجعة')->success()->send();
    }
}
