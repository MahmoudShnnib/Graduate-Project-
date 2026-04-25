<?php

namespace App\Filament\Resources\Students\Pages;

use App\Enums\EnrollmentStatus;
use App\Enums\GradeStatus;
use App\Enums\StudentStatus;
use App\Filament\Resources\Students\StudentResource;
use App\Models\AuditLog;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ViewStudent extends ViewRecord implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = StudentResource::class;

    protected string $view = 'filament.resources.students.pages.view-student';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => StudentEnrollment::query()
                ->where('student_id', $this->record->id)
                ->with(['courseOffering.course', 'courseOffering.semester', 'grade'])
                ->reorder()
                ->orderByRaw(
                    'CASE WHEN student_enrollments.status = ? '
                    .'AND NOT EXISTS (SELECT 1 FROM grades '
                    .'WHERE grades.enrollment_id = student_enrollments.id '
                    .'AND grades.status = ?) THEN 0 ELSE 1 END ASC',
                    [EnrollmentStatus::Enrolled->value, GradeStatus::Approved->value]
                )
                ->orderByDesc('course_offering_id')
            )
            ->columns([
                TextColumn::make('courseOffering.course.name')
                    ->label('المادة')
                    ->searchable(),

                TextColumn::make('courseOffering.course.code')
                    ->label('رمز المادة')
                    ->fontFamily('mono')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('courseOffering.semester.name')
                    ->label('الفصل الدراسي')
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                TextColumn::make('grade.coursework_grade')
                    ->label('أعمال الفصل')
                    ->alignCenter()
                    ->placeholder('—'),

                TextColumn::make('grade.final_grade')
                    ->label('النهائي')
                    ->alignCenter()
                    ->placeholder('—'),

                TextColumn::make('grade.total')
                    ->label('المجموع')
                    ->alignCenter()
                    ->weight('bold')
                    ->placeholder('—'),

                TextColumn::make('grade.letter')
                    ->label('التقدير')
                    ->alignCenter()
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'A', 'B+', 'B', 'C+', 'C' => 'success',
                        'D+', 'D' => 'warning',
                        'F' => 'danger',
                        default => 'gray',
                    })
                    ->placeholder('—'),

                TextColumn::make('enrollment_status_chip')
                    ->label('الحالة')
                    ->alignCenter()
                    ->badge()
                    ->state(fn (StudentEnrollment $record): string => self::computeStatusLabel($record))
                    ->color(fn (StudentEnrollment $record): string => self::computeStatusColor($record)),
            ])
            ->paginated(false)
            ->emptyStateHeading('لا توجد مقررات مسجلة لهذا الطالب.')
            ->emptyStateDescription(null)
            ->emptyStateIcon('heroicon-o-academic-cap');
    }

    public bool $showAllActivity = false;

    public function toggleShowAllActivity(): void
    {
        $this->showAllActivity = ! $this->showAllActivity;
    }

    /** @return Collection<int, array{date: \Carbon\Carbon, icon: string, color: string, title: string, detail: string|null}> */
    public function getActivityTimeline(): Collection
    {
        /** @var Student $student */
        $student = $this->record;
        $entries = collect();

        // Course surveys
        $student->courseSurveys()->with(['course', 'semester'])->each(function ($survey) use ($entries) {
            $entries->push([
                'date' => $survey->created_at,
                'icon' => 'heroicon-o-clipboard-document-list',
                'color' => 'info',
                'title' => 'قدّم استطلاع مادة: '.($survey->course?->name ?? '—'),
                'detail' => $survey->semester?->name,
            ]);
        });

        // Add/drop requests (submission + admin decision)
        $student->addDropRequests()->with(['courseOffering.course', 'decidedBy'])->each(function ($req) use ($entries) {
            $typeLabel = $req->type?->value === 'add' ? 'إضافة' : 'حذف';
            $courseName = $req->courseOffering?->course?->name ?? '—';
            $entries->push([
                'date' => $req->created_at,
                'icon' => 'heroicon-o-arrows-right-left',
                'color' => 'warning',
                'title' => "طلب {$typeLabel} مادة: {$courseName}",
                'detail' => null,
            ]);

            if ($req->decided_at) {
                $isApproved = $req->status === 'approved';
                $entries->push([
                    'date' => $req->decided_at,
                    'icon' => $isApproved ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle',
                    'color' => $isApproved ? 'success' : 'danger',
                    'title' => ($isApproved ? 'قُبل' : 'رُفض')." طلب {$typeLabel}: {$courseName}",
                    'detail' => $req->decidedBy?->name,
                ]);
            }
        });

        // Suspension requests (submission + admin decision)
        $student->suspensionRequests()->with(['semester', 'decidedBy'])->each(function ($req) use ($entries) {
            $entries->push([
                'date' => $req->created_at,
                'icon' => 'heroicon-o-pause-circle',
                'color' => 'danger',
                'title' => 'قدّم طلب إيقاف قيد',
                'detail' => $req->semester?->name,
            ]);

            if ($req->decided_at) {
                $isApproved = $req->status->value === 'approved';
                $entries->push([
                    'date' => $req->decided_at,
                    'icon' => $isApproved ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle',
                    'color' => $isApproved ? 'success' : 'danger',
                    'title' => ($isApproved ? 'قُبل' : 'رُفض').' طلب إيقاف القيد',
                    'detail' => $req->decidedBy?->name,
                ]);
            }
        });

        // Thesis registration (submission + admin decision)
        $thesis = $student->thesisRegistration()->with('decidedBy')->first();
        if ($thesis) {
            $entries->push([
                'date' => $thesis->created_at,
                'icon' => 'heroicon-o-document-text',
                'color' => 'primary',
                'title' => 'سجّل رسالة: '.$thesis->title,
                'detail' => null,
            ]);

            if ($thesis->decided_at) {
                $isApproved = $thesis->status === 'approved';
                $entries->push([
                    'date' => $thesis->decided_at,
                    'icon' => $isApproved ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle',
                    'color' => $isApproved ? 'success' : 'danger',
                    'title' => ($isApproved ? 'قُبلت' : 'رُفضت').' الرسالة',
                    'detail' => $thesis->decidedBy?->name,
                ]);
            }
        }

        // Clearance request (submission + issuance)
        $clearance = $student->clearanceRequest;
        if ($clearance) {
            $entries->push([
                'date' => $clearance->created_at,
                'icon' => 'heroicon-o-document-check',
                'color' => 'success',
                'title' => 'قدّم طلب استماحة',
                'detail' => null,
            ]);

            if ($clearance->issued_at) {
                $entries->push([
                    'date' => $clearance->issued_at,
                    'icon' => 'heroicon-o-check-badge',
                    'color' => 'success',
                    'title' => 'تم اعتماد الاستماحة',
                    'detail' => null,
                ]);
            }
        }

        // Audit logs (changes to this student's record)
        AuditLog::query()
            ->where('model_type', Student::class)
            ->where('model_id', $student->id)
            ->with('user')
            ->latest('created_at')
            ->each(function ($log) use ($entries) {
                $entries->push([
                    'date' => $log->created_at,
                    'icon' => 'heroicon-o-shield-check',
                    'color' => 'gray',
                    'title' => self::formatAuditAction($log->action, $log->new_values),
                    'detail' => $log->user?->name ?? 'النظام',
                ]);
            });

        return $entries->sortByDesc('date')->values();
    }

    private static function formatAuditAction(string $action, ?array $newValues): string
    {
        if ($action === 'Student_updated' && $newValues) {
            $parts = [];
            if (isset($newValues['status'])) {
                $statusLabel = StudentStatus::tryFrom($newValues['status'])?->label() ?? $newValues['status'];
                $parts[] = 'الحالة ← '.$statusLabel;
            }
            if (isset($newValues['gpa'])) {
                $parts[] = 'المعدل ← '.$newValues['gpa'];
            }
            if ($parts) {
                return 'تحديث بيانات: '.implode('، ', $parts);
            }
        }

        return match ($action) {
            'Student_updated' => 'تحديث بيانات الطالب',
            'suspension_approved' => 'اعتماد إيقاف القيد',
            'thesis_approved' => 'اعتماد تسجيل الرسالة',
            default => $action,
        };
    }

    private static function computeStatusLabel(StudentEnrollment $record): string
    {
        if ($record->status === EnrollmentStatus::Dropped) {
            return 'محذوف';
        }

        if ($record->status === EnrollmentStatus::Withdrawn) {
            return 'منسحب';
        }

        $approved = $record->grade && $record->grade->status === GradeStatus::Approved;

        if (! $approved) {
            return 'يدرس حالياً';
        }

        return $record->grade->letter === 'F' ? 'راسب' : 'ناجح';
    }

    private static function computeStatusColor(StudentEnrollment $record): string
    {
        if ($record->status === EnrollmentStatus::Dropped) {
            return 'gray';
        }

        if ($record->status === EnrollmentStatus::Withdrawn) {
            return 'gray';
        }

        $approved = $record->grade && $record->grade->status === GradeStatus::Approved;

        if (! $approved) {
            return 'warning';
        }

        return $record->grade->letter === 'F' ? 'danger' : 'success';
    }
}
