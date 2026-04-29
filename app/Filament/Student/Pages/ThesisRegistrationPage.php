<?php

namespace App\Filament\Student\Pages;

use App\Enums\ThesisStatus;
use App\Models\Supervisor;
use App\Models\ThesisRegistration;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\WithFileUploads;

class ThesisRegistrationPage extends Page
{
    use WithFileUploads;

    protected string $view = 'filament.student.pages.thesis-registration';

    protected static ?string $navigationLabel = 'تسجيل الرسالة';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'الطلبات';

    protected static ?int $navigationSort = 8;

    public string $thesisTitle = '';

    public string $thesisTitleEn = '';

    public string $supervisorType = 'internal';

    public ?int $selectedSupervisorId = null;

    public $proposalFile = null;

    public function getLatestRegistration(): ?ThesisRegistration
    {
        $student = auth()->user()->student;

        return $student?->latestThesisRegistration;
    }

    public function canSubmitNew(): bool
    {
        $latest = $this->getLatestRegistration();

        if (! $latest) {
            return true;
        }

        return $latest->status === ThesisStatus::Rejected;
    }

    public function isEligible(): bool
    {
        return auth()->user()->student?->isEligibleForThesis() ?? false;
    }

    public function getInternalSupervisors(): array
    {
        return User::where('role', 'faculty')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->pluck('name', 'id')
            ->all();
    }

    public function getExternalSupervisors(): array
    {
        return Supervisor::orderBy('name')
            ->get(['id', 'name'])
            ->pluck('name', 'id')
            ->all();
    }

    public function getSelectedSupervisorDetails(): ?array
    {
        if (! $this->selectedSupervisorId) {
            return null;
        }

        if ($this->supervisorType === 'internal') {
            $user = User::find($this->selectedSupervisorId);
            if (! $user) {
                return null;
            }

            return [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ];
        }

        $supervisor = Supervisor::find($this->selectedSupervisorId);
        if (! $supervisor) {
            return null;
        }

        return [
            'name' => $supervisor->name,
            'academic_rank' => $supervisor->academic_rank,
            'qualification' => $supervisor->qualification,
            'institution' => $supervisor->institution,
            'email' => $supervisor->email,
            'phone' => $supervisor->phone,
        ];
    }

    public function getCoursesCompletionDate(): ?string
    {
        $student = auth()->user()->student;
        if (! $student) {
            return null;
        }

        $lastSemester = $student->enrollments()
            ->whereHas('grade', fn ($q) => $q->where('status', 'approved'))
            ->join('course_offerings', 'student_enrollments.course_offering_id', '=', 'course_offerings.id')
            ->join('semesters', 'course_offerings.semester_id', '=', 'semesters.id')
            ->orderByDesc('semesters.end_date')
            ->select('semesters.end_date')
            ->first();

        return $lastSemester?->end_date;
    }

    public function updatedSupervisorType(): void
    {
        $this->selectedSupervisorId = null;
    }

    public function submit(): void
    {
        $student = auth()->user()->student;

        if (! $student) {
            Notification::make()->title('لا يوجد سجل طالب')->danger()->send();

            return;
        }

        if (! $student->canPerformActions()) {
            Notification::make()
                ->title('لا يمكنك تقديم هذا الطلب')
                ->body('حسابك ليس في حالة نشطة حالياً.')
                ->danger()
                ->send();

            return;
        }

        if (! $student->isEligibleForThesis()) {
            Notification::make()
                ->title('غير مؤهل')
                ->body('يجب إتمام جميع المقررات المطلوبة بمعدل 3.0 أو أعلى')
                ->danger()
                ->send();

            return;
        }

        if (! $this->canSubmitNew()) {
            Notification::make()->title('لديك طلب قائم بالفعل')->warning()->send();

            return;
        }

        $rules = [
            'thesisTitle' => 'required|min:5',
            'thesisTitleEn' => 'required|min:5',
            'selectedSupervisorId' => 'required',
            'proposalFile' => 'required|file|max:10240',
        ];

        $messages = [
            'thesisTitle.required' => 'يرجى إدخال عنوان الرسالة باللغة العربية',
            'thesisTitle.min' => 'يجب أن يكون العنوان 5 أحرف على الأقل',
            'thesisTitleEn.required' => 'يرجى إدخال عنوان الرسالة باللغة الإنجليزية',
            'thesisTitleEn.min' => 'يجب أن يكون العنوان 5 أحرف على الأقل',
            'selectedSupervisorId.required' => 'يرجى اختيار المشرف',
            'proposalFile.required' => 'يرجى رفع ملف المقترح البحثي',
            'proposalFile.max' => 'حجم الملف يجب أن لا يتجاوز 10 ميجابايت',
        ];

        $this->validate($rules, $messages);

        $proposalPath = $this->proposalFile->store('proposals', 'public');

        $data = [
            'student_id' => $student->id,
            'title' => $this->thesisTitle,
            'title_en' => $this->thesisTitleEn,
            'is_external_supervisor' => $this->supervisorType === 'external',
            'proposal_path' => $proposalPath,
            'status' => ThesisStatus::Pending,
        ];

        if ($this->supervisorType === 'internal') {
            $data['faculty_supervisor_id'] = $this->selectedSupervisorId;
        } else {
            $data['supervisor_id'] = $this->selectedSupervisorId;
        }

        ThesisRegistration::create($data);

        $this->thesisTitle = '';
        $this->thesisTitleEn = '';
        $this->supervisorType = 'internal';
        $this->selectedSupervisorId = null;
        $this->proposalFile = null;

        Notification::make()->title('تم إرسال طلب تسجيل الرسالة للمراجعة')->success()->send();
    }
}
