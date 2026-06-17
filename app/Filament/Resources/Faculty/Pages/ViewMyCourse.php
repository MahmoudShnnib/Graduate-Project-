<?php

namespace App\Filament\Resources\Faculty\Pages;

use App\Enums\GradeStatus;
use App\Filament\Resources\Faculty\MyCourseResource;
use App\Models\CourseOffering;
use App\Models\Grade;
use App\Models\StudentEnrollment;
use App\Services\AuditService;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ViewMyCourse extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = MyCourseResource::class;

    protected string $view = 'filament.resources.faculty.pages.view-my-course';

    public CourseOffering $record;

    public function boot(): void
    {
        if (isset($this->record) && $this->record->faculty_id !== auth()->id()) {
            abort(403);
        }
    }

    public function getTitle(): string
    {
        return $this->record->course->name.' — '.$this->record->semester->name;
    }

    public function getBreadcrumb(): string
    {
        return $this->record->course->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print_results')
                ->label('طباعة نتائج المادة')
                ->icon(Heroicon::Printer)
                ->color('gray')
                ->url(fn (): string => route('print.course-results', $this->record))
                ->openUrlInNewTab(),

            Action::make('grade_distribution')
                ->label('توزيع الدرجات')
                ->icon(Heroicon::AdjustmentsHorizontal)
                ->color('gray')
                ->modalHeading('توزيع الدرجات')
                ->modalDescription('حدد كيفية توزيع الدرجة من 100 بين أعمال الفصل والاختبار النهائي.')
                ->modalSubmitActionLabel('حفظ التوزيع')
                ->fillForm(fn (): array => [
                    'coursework_max' => $this->record->coursework_max ?? 40,
                    'final_max' => $this->record->final_max ?? 60,
                ])
                ->form([
                    TextInput::make('coursework_max')
                        ->label('أعمال الفصل')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(99)
                        ->required()
                        ->live(debounce: 250)
                        ->default(40)
                        ->rule(fn (Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get): void {
                            $sum = (int) $value + (int) $get('final_max');
                            if ($sum !== 100) {
                                $fail("مجموع التوزيع يجب أن يساوي 100 (المجموع الحالي: {$sum})");
                            }
                        }),

                    TextInput::make('final_max')
                        ->label('الاختبار النهائي')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(99)
                        ->required()
                        ->live(debounce: 250)
                        ->default(60),

                    Placeholder::make('distribution_total')
                        ->hiddenLabel()
                        ->content(function (Get $get): \Illuminate\Support\HtmlString {
                            $sum = (int) $get('coursework_max') + (int) $get('final_max');
                            $isValid = $sum === 100;
                            $color = $isValid ? '#166534' : '#991b1b';
                            $bg = $isValid ? '#f0fdf4' : '#fef2f2';
                            $border = $isValid ? '#dcfce7' : '#fee2e2';
                            $icon = $isValid ? '✓' : '⚠';
                            $label = $isValid ? 'المجموع = 100' : "المجموع = {$sum} (يجب أن يساوي 100)";

                            return new \Illuminate\Support\HtmlString(
                                "<div style=\"display:inline-flex;align-items:center;gap:8px;padding:8px 14px;border-radius:8px;background:{$bg};border:1px solid {$border};color:{$color};font-weight:600;font-size:13px;\">"
                                ."<span>{$icon}</span><span>{$label}</span>"
                                .'</div>'
                            );
                        }),
                ])
                ->action(function (array $data): void {
                    // Inline rule on coursework_max already blocks the form
                    // submit when sum ≠ 100. By the time we reach here the
                    // distribution is guaranteed valid.
                    $this->record->update([
                        'coursework_max' => (int) $data['coursework_max'],
                        'final_max' => (int) $data['final_max'],
                    ]);

                    Notification::make()
                        ->title('تم حفظ توزيع الدرجات بنجاح')
                        ->success()
                        ->send();
                }),

            Action::make('submit_grades')
                ->label('إرسال الدرجات للاعتماد')
                ->icon(Heroicon::PaperAirplane)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('إرسال الدرجات للاعتماد')
                ->modalDescription('سيتم إرسال الدرجات المكتملة (أعمال الفصل + الامتحان النهائي) إلى الإدارة للمراجعة والاعتماد. لا يمكن تعديلها بعد الإرسال. لن تُرسل الدرجات الناقصة.')
                ->modalSubmitActionLabel('إرسال الآن')
                ->action(function (AuditService $audit): void {
                    $draftQuery = fn () => Grade::whereHas('enrollment', fn (Builder $q) => $q->where('course_offering_id', $this->record->id))
                        ->where('status', GradeStatus::Draft);

                    // A grade may only be submitted once both marks are entered.
                    $incomplete = (clone $draftQuery)()->whereNull('final_grade')->count();

                    $count = (clone $draftQuery)()->whereNotNull('final_grade')->whereNotNull('coursework_grade')
                        ->update(['status' => GradeStatus::PendingApproval]);

                    if ($count === 0) {
                        Notification::make()
                            ->title('لا توجد درجات مكتملة للإرسال')
                            ->body($incomplete > 0 ? "يوجد {$incomplete} مسودة بدون درجة امتحان نهائي." : null)
                            ->warning()
                            ->send();

                        return;
                    }

                    $audit->log('grade_submitted', $this->record);

                    Notification::make()
                        ->title("تم إرسال {$count} درجة للاعتماد")
                        ->body($incomplete > 0 ? "لم تُرسل {$incomplete} درجة ناقصة (بدون امتحان نهائي)." : null)
                        ->success()
                        ->send();
                })
                ->visible(function (): bool {
                    return Grade::whereHas('enrollment', fn (Builder $q) => $q->where('course_offering_id', $this->record->id))
                        ->where('status', GradeStatus::Draft)
                        ->exists();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        $offeringId = $this->record->id;

        return $table
            ->query(
                StudentEnrollment::query()
                    ->where('course_offering_id', $offeringId)
                    ->active()
                    ->with(['student.user', 'grade'])
            )
            ->columns([
                TextColumn::make('student.enrollment_no')
                    ->label('رقم القيد')
                    ->searchable(),

                TextColumn::make('student.user.name')
                    ->label('اسم الطالب')
                    ->searchable(),

                TextColumn::make('grade.coursework_grade')
                    ->label('أعمال الفصل ('.$this->record->coursework_max.')')
                    ->placeholder('—')
                    ->numeric(2),

                TextColumn::make('grade.final_grade')
                    ->label('الامتحان النهائي ('.$this->record->final_max.')')
                    ->placeholder('—')
                    ->numeric(2),

                TextColumn::make('grade.total')
                    ->label('المجموع')
                    ->placeholder('—')
                    ->numeric(2),

                TextColumn::make('grade.letter')
                    ->label('التقدير')
                    ->placeholder('—')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'A' => 'success',
                        'B+', 'B' => 'info',
                        'C+', 'C' => 'warning',
                        'D+', 'D' => 'gray',
                        'F' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('grade.status')
                    ->label('الحالة')
                    ->placeholder('لم تُدخل')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => $state instanceof GradeStatus ? $state->label() : '—')
                    ->color(fn (mixed $state): string => match (true) {
                        $state === GradeStatus::Approved => 'success',
                        $state === GradeStatus::PendingApproval => 'warning',
                        $state === GradeStatus::Draft => 'info',
                        default => 'gray',
                    }),
            ])
            ->recordActions([
                Action::make('enter_grade')
                    ->label('إدخال الدرجة')
                    ->icon(Heroicon::PencilSquare)
                    ->color('primary')
                    ->visible(function (StudentEnrollment $record): bool {
                        $grade = $record->grade;

                        // Editable only before it is sent for approval: no grade
                        // yet, or still a draft. Pending/approved grades are locked.
                        return $grade === null || $grade->status === GradeStatus::Draft;
                    })
                    ->modalHeading(fn (StudentEnrollment $record): string => 'إدخال درجة — '.$record->student->user->name)
                    ->form([
                        TextInput::make('coursework_grade')
                            ->label('أعمال الفصل (من '.$this->record->coursework_max.')')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue($this->record->coursework_max)
                            ->step(0.5)
                            ->required(),

                        TextInput::make('final_grade')
                            ->label('الامتحان النهائي (من '.$this->record->final_max.')')
                            ->helperText('يمكن حفظ أعمال الفصل كمسودة الآن، لكن يجب إدخال الامتحان النهائي قبل إرسال الدرجات للاعتماد.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue($this->record->final_max)
                            ->step(0.5),
                    ])
                    ->fillForm(fn (StudentEnrollment $record): array => [
                        'coursework_grade' => $record->grade?->coursework_grade,
                        'final_grade' => $record->grade?->final_grade,
                    ])
                    ->action(function (StudentEnrollment $record, array $data): void {
                        $finalGrade = ($data['final_grade'] !== null && $data['final_grade'] !== '')
                            ? $data['final_grade']
                            : null;

                        Grade::updateOrCreate(
                            ['enrollment_id' => $record->id],
                            [
                                'coursework_grade' => $data['coursework_grade'],
                                'final_grade' => $finalGrade,
                                'status' => GradeStatus::Draft,
                            ]
                        );

                        Notification::make()
                            ->title('تم حفظ الدرجة كمسودة')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('لا يوجد طلاب مسجلون في هذه المادة')
            ->emptyStateDescription('سيظهر الطلاب هنا بعد تسجيلهم من قبل الإدارة.');
    }
}
