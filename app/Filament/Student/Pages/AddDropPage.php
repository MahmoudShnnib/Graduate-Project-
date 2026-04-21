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

    /** @var array<int, bool> */
    public array $selectedDropEnrollments = [];

    /** @var array<int, bool> */
    public array $selectedAddOfferings = [];

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
            ->active()
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
            ->active()
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

    /**
     * Returns course_offering_ids where the student has a pending Drop request
     * for the current active semester. Used to mark rows as "submitted, awaiting
     * review" in the UI so students can see what they chose after submitting.
     *
     * @return array<int, int>
     */
    public function getPendingDropOfferingIds(): array
    {
        $student = auth()->user()->student;
        $activeSemester = $this->getActiveSemester();

        if (! $student || ! $activeSemester) {
            return [];
        }

        return AddDropRequest::where('student_id', $student->id)
            ->where('type', AddDropType::Drop)
            ->where('status', 'pending')
            ->whereHas('courseOffering', fn ($q) => $q->where('semester_id', $activeSemester->id))
            ->pluck('course_offering_id')
            ->all();
    }

    /**
     * Returns course_offering_ids where the student has a pending Add request
     * for the current active semester.
     *
     * @return array<int, int>
     */
    public function getPendingAddOfferingIds(): array
    {
        $student = auth()->user()->student;
        $activeSemester = $this->getActiveSemester();

        if (! $student || ! $activeSemester) {
            return [];
        }

        return AddDropRequest::where('student_id', $student->id)
            ->where('type', AddDropType::Add)
            ->where('status', 'pending')
            ->whereHas('courseOffering', fn ($q) => $q->where('semester_id', $activeSemester->id))
            ->pluck('course_offering_id')
            ->all();
    }

    /**
     * All of the student's add/drop requests for the active semester —
     * pending, approved, rejected. Used to render a "My submissions" block
     * so the student can see the full history of decisions on their choices,
     * including approved drops (which disappear from the active enrollment
     * list) and rejected adds (which still appear in the available list).
     */
    public function getAllRequestsThisSemester(): Collection
    {
        $student = auth()->user()->student;
        $activeSemester = $this->getActiveSemester();

        if (! $student || ! $activeSemester) {
            return collect();
        }

        return AddDropRequest::where('student_id', $student->id)
            ->whereHas('courseOffering', fn ($q) => $q->where('semester_id', $activeSemester->id))
            ->with(['courseOffering.course'])
            ->orderBy('type')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @return array<int>
     */
    private function selectedDropIds(): array
    {
        return array_keys(array_filter($this->selectedDropEnrollments));
    }

    /**
     * @return array<int>
     */
    private function selectedAddIds(): array
    {
        return array_keys(array_filter($this->selectedAddOfferings));
    }

    public function submitRequests(): void
    {
        if (! $this->isAddDropOpen()) {
            Notification::make()->title('فترة الإضافة والحذف مغلقة')->danger()->send();

            return;
        }

        $student = auth()->user()->student;
        $activeSemester = $this->getActiveSemester();

        if ($student && $activeSemester && ! $student->canPerformActions($activeSemester)) {
            Notification::make()
                ->title('لا يمكنك تقديم طلبات إضافة أو حذف')
                ->body('حسابك ليس في حالة نشطة أو أن قيدك موقوف لهذا الفصل.')
                ->danger()
                ->send();

            return;
        }

        if ($this->hasSubmittedThisSemester()) {
            Notification::make()->title('لديك طلب قائم بالفعل لهذا الفصل')->warning()->send();

            return;
        }

        $dropIds = $this->selectedDropIds();
        $addIds = $this->selectedAddIds();

        if (empty($dropIds) && empty($addIds)) {
            Notification::make()->title('يرجى اختيار مقرر واحد على الأقل للإضافة أو الحذف')->warning()->send();

            return;
        }

        $student = auth()->user()->student;

        if (count($dropIds) + $this->getDropCount() > self::MAX_DROPS) {
            Notification::make()->title('اخترت عدداً يتجاوز الحد الأقصى ('.self::MAX_DROPS.') لطلبات الحذف')->warning()->send();

            return;
        }

        if (count($addIds) + $this->getAddCount() > self::MAX_ADDS) {
            Notification::make()->title('اخترت عدداً يتجاوز الحد الأقصى ('.self::MAX_ADDS.') لطلبات الإضافة')->warning()->send();

            return;
        }

        $dropEnrollments = StudentEnrollment::whereIn('id', $dropIds)
            ->where('student_id', $student->id)
            ->get();

        if ($dropEnrollments->count() !== count($dropIds)) {
            abort(403);
        }

        $validAddOfferings = CourseOffering::whereIn('id', $addIds)
            ->whereHas('course.specializations', fn ($q) => $q->where('specializations.id', $student->specialization_id))
            ->pluck('id')
            ->all();

        if (count($validAddOfferings) !== count($addIds)) {
            Notification::make()->title('أحد المقررات المختارة غير متاح لتخصصك')->danger()->send();

            return;
        }

        foreach ($dropEnrollments as $enrollment) {
            AddDropRequest::create([
                'student_id' => $student->id,
                'course_offering_id' => $enrollment->course_offering_id,
                'type' => AddDropType::Drop,
                'status' => 'pending',
                'reason' => $this->dropReason ?: null,
            ]);
        }

        foreach ($validAddOfferings as $offeringId) {
            AddDropRequest::create([
                'student_id' => $student->id,
                'course_offering_id' => $offeringId,
                'type' => AddDropType::Add,
                'status' => 'pending',
                'reason' => $this->addReason ?: null,
            ]);
        }

        $this->selectedDropEnrollments = [];
        $this->selectedAddOfferings = [];
        $this->dropReason = '';
        $this->addReason = '';

        $total = count($dropIds) + count($addIds);
        Notification::make()->title("تم إرسال {$total} طلب للمراجعة")->success()->send();
    }
}
