<?php

namespace App\Filament\Student\Pages;

use App\Models\ThesisRegistration;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ThesisRegistrationPage extends Page
{
    protected string $view = 'filament.student.pages.thesis-registration';

    protected static ?string $navigationLabel = 'تسجيل الرسالة';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 8;

    public string $thesisTitle = '';

    public string $supervisorName = '';

    public string $supervisorDegree = '';

    public string $supervisorEmployer = '';

    public function getRegistration(): ?ThesisRegistration
    {
        $student = auth()->user()->student;

        return $student?->thesisRegistration;
    }

    public function isEligible(): bool
    {
        return auth()->user()->student?->isEligibleForThesis() ?? false;
    }

    public function submit(): void
    {
        $student = auth()->user()->student;

        if (! $student) {
            Notification::make()->title('لا يوجد سجل طالب')->danger()->send();

            return;
        }

        if (! $student->isEligibleForThesis()) {
            Notification::make()
                ->title('غير مؤهل')
                ->body('يجب إتمام 10 مقررات على الأقل بمعدل 3.0 أو أعلى')
                ->danger()
                ->send();

            return;
        }

        if ($student->thesisRegistration) {
            Notification::make()->title('لقد سجلت رسالة من قبل')->warning()->send();

            return;
        }

        $this->validate([
            'thesisTitle' => 'required|min:5',
            'supervisorName' => 'required',
            'supervisorDegree' => 'required',
            'supervisorEmployer' => 'required',
        ], [
            'thesisTitle.required' => 'يرجى إدخال عنوان الرسالة',
            'thesisTitle.min' => 'يجب أن يكون العنوان 5 أحرف على الأقل',
            'supervisorName.required' => 'يرجى إدخال اسم المشرف',
            'supervisorDegree.required' => 'يرجى إدخال درجة المشرف العلمية',
            'supervisorEmployer.required' => 'يرجى إدخال جهة عمل المشرف',
        ]);

        ThesisRegistration::create([
            'student_id' => $student->id,
            'title' => $this->thesisTitle,
            'supervisor_name' => $this->supervisorName,
            'supervisor_degree' => $this->supervisorDegree,
            'supervisor_employer' => $this->supervisorEmployer,
            'status' => 'pending',
        ]);

        $this->title = '';
        $this->supervisorName = '';
        $this->supervisorDegree = '';
        $this->supervisorEmployer = '';

        Notification::make()->title('تم إرسال طلب تسجيل الرسالة للمراجعة')->success()->send();
    }
}
