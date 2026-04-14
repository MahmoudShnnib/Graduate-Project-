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

    public const int MAX_DROPS = 3;

    public const int MAX_ADDS = 3;

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
            ->whereHas('course.specializations', fn ($q) => $q->where('specializations.id', $student->specialization_id))
            ->with('course')
            ->get();
    }

    public function getDropCount(): int
    {
        $student = auth()->user()->student;
        $activeSemester = $this->getActiveSemester();

        if (! $student || ! $activeSemester) {
            return 0;
        }

        return AddDropRequest::where('student_id', $student->id)
            ->where('type', AddDropType::Drop)
            ->whereHas('courseOffering', fn ($q) => $q->where('semester_id', $activeSemester->id))
            ->whereIn('status', ['pending', 'approved'])
            ->count();
    }

    public function getAddCount(): int
    {
        $student = auth()->user()->student;
        $activeSemester = $this->getActiveSemester();

        if (! $student || ! $activeSemester) {
            return 0;
        }

        return AddDropRequest::where('student_id', $student->id)
            ->where('type', AddDropType::Add)
            ->whereHas('courseOffering', fn ($q) => $q->where('semester_id', $activeSemester->id))
            ->whereIn('status', ['pending', 'approved'])
            ->count();
    }

    public function hasSubmittedThisSemester(): bool
    {
        $student = auth()->user()->student;
        $activeSemester = $this->getActiveSemester();

        if (! $student || ! $activeSemester) {
            return false;
        }

        return AddDropRequest::where('student_id', $student->id)
            ->whereHas('courseOffering', fn ($q) => $q->where('semester_id', $activeSemester->id))
            ->whereIn('status', ['pending', 'approved'])
            ->exists();
    }

    public function requestDrop(int $enrollmentId): void
    {
        if (! $this->isAddDropOpen()) {
            Notification::make()->title('فترة الإضافة والحذف مغلقة')->danger()->send();

            return;
        }

        if ($this->hasSubmittedThisSemester()) {
            Notification::make()->title('لديك طلب قائم بالفعل لهذا الفصل')->warning()->send();

            return;
        }

        if ($this->getDropCount() >= self::MAX_DROPS) {
            Notification::make()->title('وصلت للحد الأقصى ('.self::MAX_DROPS.') لطلبات الحذف')->warning()->send();

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

        if ($this->hasSubmittedThisSemester()) {
            Notification::make()->title('لديك طلب قائم بالفعل لهذا الفصل')->warning()->send();

            return;
        }

        if (! $this->addOfferingId) {
            Notification::make()->title('يرجى اختيار مقرر')->warning()->send();

            return;
        }

        if ($this->getAddCount() >= self::MAX_ADDS) {
            Notification::make()->title('وصلت للحد الأقصى ('.self::MAX_ADDS.') لطلبات الإضافة')->warning()->send();

            return;
        }

        $student = auth()->user()->student;

        // Verify the offering belongs to the student's specialization
        $offering = CourseOffering::where('id', $this->addOfferingId)
            ->whereHas('course.specializations', fn ($q) => $q->where('specializations.id', $student->specialization_id))
            ->first();

        if (! $offering) {
            Notification::make()->title('المقرر المختار غير متاح لتخصصك')->danger()->send();

            return;
        }

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
