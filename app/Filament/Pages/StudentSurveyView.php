<?php

namespace App\Filament\Pages;

use App\Actions\Enrollment\DropStudentFromOffering;
use App\Actions\Enrollment\EnrollStudentInOffering;
use App\Exceptions\CourseOfferingClosedException;
use App\Exceptions\CourseOutsideSpecializationException;
use App\Exceptions\EnrollmentCapReachedException;
use App\Exceptions\StudentNotActiveException;
use App\Exceptions\StudentSuspendedInSemesterException;
use App\Models\CourseOffering;
use App\Models\CourseSurvey;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class StudentSurveyView extends Page
{
    protected string $view = 'filament.pages.student-survey-view';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static bool $shouldRegisterNavigation = false;

    public ?Student $student = null;

    public ?Semester $semester = null;

    /** @var array<int, string> */
    public array $selected = [];

    public ?int $retakeActionCourseId = null;

    public ?string $retakeActionType = null;

    public function showRetakeAction(int $courseId, string $type): void
    {
        $this->retakeActionCourseId = $courseId;
        $this->retakeActionType = $type;
    }

    public function cancelRetakeAction(): void
    {
        $this->retakeActionCourseId = null;
        $this->retakeActionType = null;
    }

    public function confirmRetakeAction(): void
    {
        if (! $this->retakeActionCourseId || ! $this->retakeActionType) {
            return;
        }

        if ($this->retakeActionType === 'approve') {
            $this->approveRetake($this->retakeActionCourseId);
        } else {
            $this->rejectRetake($this->retakeActionCourseId);
        }

        $this->retakeActionCourseId = null;
        $this->retakeActionType = null;
    }

    public function mount(Student $student, Semester $semester): void
    {
        $this->student = $student->load(['user:id,name', 'specialization:id,name']);
        $this->semester = $semester;
    }

    public function getTitle(): string
    {
        if (! $this->student || ! $this->semester) {
            return 'استطلاع الطالب';
        }

        return 'استطلاع '.$this->student->user?->name.' — '.$this->semester->name;
    }

    public function getSurveyEntries(): Collection
    {
        return CourseSurvey::where('student_id', $this->student->id)
            ->where('semester_id', $this->semester->id)
            ->where('is_retake', false)
            ->with('course')
            ->orderBy('priority')
            ->get()
            ->map(fn (CourseSurvey $survey) => $this->buildSurveyEntry($survey));
    }

    public function getRetakeEntries(): Collection
    {
        return CourseSurvey::where('student_id', $this->student->id)
            ->where('semester_id', $this->semester->id)
            ->where('is_retake', true)
            ->with('course')
            ->get()
            ->map(fn (CourseSurvey $survey) => $this->buildSurveyEntry($survey));
    }

    private function buildSurveyEntry(CourseSurvey $survey): object
    {
        $offering = CourseOffering::where('course_id', $survey->course_id)
            ->where('semester_id', $this->semester->id)
            ->first();

        $enrollment = null;
        if ($offering) {
            $enrollment = StudentEnrollment::where('student_id', $this->student->id)
                ->where('course_offering_id', $offering->id)
                ->active()
                ->first();
        }

        return (object) [
            'survey' => $survey,
            'course' => $survey->course,
            'offering' => $offering,
            'enrollment' => $enrollment,
            'is_retake' => $survey->is_retake,
        ];
    }

    public function enrollSelected(): void
    {
        $enroll = app(EnrollStudentInOffering::class);
        if (empty($this->selected)) {
            Notification::make()->title('الرجاء تحديد مقرر واحد على الأقل')->warning()->send();

            return;
        }

        // Fail-fast on suspension: if the student is suspended in this semester
        // they shouldn't be processed through the loop at all.
        if ($this->student->isSuspendedInSemester($this->semester)) {
            Notification::make()
                ->title('قيد الطالب موقوف لهذا الفصل')
                ->body('لا يمكن تسجيل الطالب في أي مقرر خلال فترة إيقاف القيد.')
                ->danger()
                ->send();

            return;
        }

        $count = 0;
        $missing = 0;
        $outOfSpec = 0;
        $closed = 0;
        $capped = false;

        foreach ($this->selected as $courseId) {
            $offering = CourseOffering::query()
                ->where('course_id', (int) $courseId)
                ->where('semester_id', $this->semester->id)
                ->first();

            if (! $offering) {
                $missing++;

                continue;
            }

            try {
                $enrollment = $enroll($this->student, $offering);
            } catch (CourseOfferingClosedException $e) {
                $closed++;

                continue;
            } catch (StudentNotActiveException $e) {
                Notification::make()
                    ->title('الطالب غير نشط')
                    ->body('لا يمكن تسجيل هذا الطالب لأن حسابه ليس في حالة نشطة.')
                    ->danger()
                    ->send();

                return;
            } catch (StudentSuspendedInSemesterException $e) {
                // Unreachable given the upfront check, but defensive.
                Notification::make()->title('قيد الطالب موقوف')->danger()->send();

                return;
            } catch (CourseOutsideSpecializationException $e) {
                $outOfSpec++;

                continue;
            } catch (EnrollmentCapReachedException $e) {
                $capped = true;
                break;
            }

            if ($enrollment->wasRecentlyCreated || $enrollment->wasChanged('status')) {
                $count++;
            }
        }

        $this->selected = [];

        if ($capped) {
            Notification::make()
                ->title("تم تسجيل {$count} مقرر، ثم تجاوز الحد الأقصى")
                ->body('لا يمكن تسجيل الطالب في أكثر من '.EnrollStudentInOffering::MAX_ENROLLMENTS_PER_SEMESTER.' مقررات في الفصل الواحد.')
                ->warning()
                ->send();

            return;
        }

        if ($missing > 0 || $outOfSpec > 0 || $closed > 0) {
            $bodyParts = [];
            if ($missing > 0) {
                $bodyParts[] = "{$missing} مقرر غير مفتوح هذا الفصل.";
            }
            if ($outOfSpec > 0) {
                $bodyParts[] = "{$outOfSpec} مقرر خارج تخصص الطالب.";
            }
            if ($closed > 0) {
                $bodyParts[] = "{$closed} مقرر مغلق للتسجيل.";
            }

            Notification::make()
                ->title("تم تسجيل {$count} مقرر")
                ->body(implode(' ', $bodyParts))
                ->warning()
                ->send();

            return;
        }

        Notification::make()->title("تم تسجيل {$count} مقرر بنجاح")->success()->send();
    }

    /**
     * Bulk reject: marks every selected survey entry as rejected in one go.
     * Skips entries that are already enrolled (admin must unenroll first via
     * the per-row button) or already rejected (idempotent skip).
     */
    public function rejectSelected(): void
    {
        if (empty($this->selected)) {
            Notification::make()->title('الرجاء تحديد مقرر واحد على الأقل')->warning()->send();

            return;
        }

        $rejected = 0;
        $skippedEnrolled = 0;
        $skippedAlreadyRejected = 0;

        foreach ($this->selected as $courseId) {
            $courseId = (int) $courseId;

            $survey = CourseSurvey::query()
                ->where('student_id', $this->student->id)
                ->where('semester_id', $this->semester->id)
                ->where('course_id', $courseId)
                ->first();

            if (! $survey) {
                continue;
            }

            if ($survey->rejected_at !== null) {
                $skippedAlreadyRejected++;

                continue;
            }

            $isEnrolled = StudentEnrollment::query()
                ->where('student_id', $this->student->id)
                ->whereHas('courseOffering', fn ($q) => $q->where('course_id', $courseId)->where('semester_id', $this->semester->id))
                ->active()
                ->exists();

            if ($isEnrolled) {
                $skippedEnrolled++;

                continue;
            }

            $survey->update([
                'is_processed' => true,
                'rejected_at' => now(),
            ]);

            $rejected++;
        }

        $this->selected = [];

        if ($skippedEnrolled > 0) {
            Notification::make()
                ->title("تم رفض {$rejected} مقرر")
                ->body("تم تخطي {$skippedEnrolled} مقرر — الطالب مسجل فيها (ألغِ التسجيل أولاً).")
                ->warning()
                ->send();

            return;
        }

        Notification::make()->title("تم رفض {$rejected} مقرر")->success()->send();
    }

    public function enrollStudent(int $courseId): void
    {
        $enroll = app(EnrollStudentInOffering::class);
        $offering = CourseOffering::query()
            ->where('course_id', $courseId)
            ->where('semester_id', $this->semester->id)
            ->first();

        if (! $offering) {
            Notification::make()->title('المقرر غير مفتوح في هذا الفصل')->danger()->send();

            return;
        }

        try {
            $enrollment = $enroll($this->student, $offering);
        } catch (CourseOfferingClosedException $e) {
            Notification::make()
                ->title('المقرر مغلق للتسجيل')
                ->body('لا يمكن تسجيل الطالب في هذه المادة حتى يتم فتحها.')
                ->danger()
                ->send();

            return;
        } catch (StudentNotActiveException $e) {
            Notification::make()
                ->title('الطالب غير نشط')
                ->body('لا يمكن تسجيل هذا الطالب لأن حسابه ليس في حالة نشطة.')
                ->danger()
                ->send();

            return;
        } catch (StudentSuspendedInSemesterException $e) {
            Notification::make()
                ->title('قيد الطالب موقوف لهذا الفصل')
                ->body('لا يمكن تسجيل الطالب في أي مقرر خلال فترة إيقاف القيد.')
                ->danger()
                ->send();

            return;
        } catch (CourseOutsideSpecializationException $e) {
            Notification::make()
                ->title('المقرر خارج تخصص الطالب')
                ->body('لا يمكن تسجيل الطالب في مقرر لا ينتمي إلى تخصصه.')
                ->danger()
                ->send();

            return;
        } catch (EnrollmentCapReachedException $e) {
            Notification::make()
                ->title('تجاوز الحد الأقصى للمقررات')
                ->body('لا يمكن تسجيل الطالب في أكثر من '.EnrollStudentInOffering::MAX_ENROLLMENTS_PER_SEMESTER.' مقررات في الفصل الواحد.')
                ->danger()
                ->send();

            return;
        }

        if ($enrollment->wasRecentlyCreated || $enrollment->wasChanged('status')) {
            Notification::make()->title('تم تسجيل الطالب في المقرر بنجاح')->success()->send();
        } else {
            Notification::make()->title('الطالب مسجل بالفعل في هذا المقرر')->warning()->send();
        }
    }

    public function approveRetake(int $courseId): void
    {
        $this->enrollStudent($courseId);
    }

    public function rejectRetake(int $courseId): void
    {
        $survey = CourseSurvey::query()
            ->where('student_id', $this->student->id)
            ->where('semester_id', $this->semester->id)
            ->where('course_id', $courseId)
            ->where('is_retake', true)
            ->first();

        if (! $survey) {
            return;
        }

        $survey->update([
            'is_processed' => true,
            'rejected_at' => now(),
        ]);

        Notification::make()->title('تم رفض طلب الإعادة')->success()->send();
    }

    public function unenrollStudent(int $courseId): void
    {
        $drop = app(DropStudentFromOffering::class);
        $offering = CourseOffering::query()
            ->where('course_id', $courseId)
            ->where('semester_id', $this->semester->id)
            ->first();

        if (! $offering) {
            return;
        }

        if ($drop($this->student, $offering)) {
            Notification::make()->title('تم إلغاء تسجيل الطالب')->warning()->send();
        }
    }

    /**
     * Marks a surveyed course as rejected for this student in this semester.
     * Sets is_processed=true so the entry disappears from the "طلبوا المادة"
     * pending list, plus a rejected_at timestamp so the student can see
     * a "مرفوض" badge on their own survey page.
     *
     * Idempotent — re-calling on an already-rejected survey is a no-op.
     */
    public function rejectCourse(int $courseId): void
    {
        $survey = CourseSurvey::query()
            ->where('student_id', $this->student->id)
            ->where('semester_id', $this->semester->id)
            ->where('course_id', $courseId)
            ->first();

        if (! $survey) {
            Notification::make()->title('لم يتم العثور على الاختيار')->danger()->send();

            return;
        }

        // Guard: don't reject an already-enrolled course — admin should
        // unenroll first if they want to undo.
        $isEnrolled = StudentEnrollment::query()
            ->where('student_id', $this->student->id)
            ->whereHas('courseOffering', fn ($q) => $q->where('course_id', $courseId)->where('semester_id', $this->semester->id))
            ->active()
            ->exists();

        if ($isEnrolled) {
            Notification::make()
                ->title('الطالب مسجل في هذا المقرر')
                ->body('يرجى إلغاء التسجيل أولاً إذا كنت تريد رفض هذا الاختيار.')
                ->warning()
                ->send();

            return;
        }

        $survey->update([
            'is_processed' => true,
            'rejected_at' => now(),
        ]);

        Notification::make()->title('تم رفض الاختيار')->success()->send();
    }

    public static function getRoutePath(Panel $panel): string
    {
        return '/student-survey/{student}/{semester}';
    }
}
