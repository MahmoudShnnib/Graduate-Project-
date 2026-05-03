<?php

namespace App\Filament\Resources\Faculty\Pages;

use App\Enums\GradeStatus;
use App\Filament\Resources\Faculty\MyCourseResource;
use App\Models\CourseOffering;
use App\Models\Grade;
use App\Models\StudentEnrollment;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
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
            Action::make('grade_distribution')
                ->label('توزيع الدرجات')
                ->icon(Heroicon::AdjustmentsHorizontal)
                ->color('gray')
                ->modalHeading('توزيع الدرجات')
                ->modalDescription('حدد كيفية توزيع الدرجة من 100 بين أعمال الفصل والاختبار النهائي.')
                ->modalSubmitActionLabel('حفظ التوزيع')
                ->fillForm(fn (): array => [
                    'coursework_max' => $this->record->coursework_max,
                    'final_max' => $this->record->final_max,
                ])
                ->form([
                    TextInput::make('coursework_max')
                        ->label('أعمال الفصل')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(99)
                        ->required(),

                    TextInput::make('final_max')
                        ->label('الاختبار النهائي')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(99)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    if ((int) $data['coursework_max'] + (int) $data['final_max'] !== 100) {
                        Notification::make()
                            ->title('مجموع التوزيع يجب أن يساوي 100')
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->record->update([
                        'coursework_max' => (int) $data['coursework_max'],
                        'final_max' => (int) $data['final_max'],
                    ]);

                    Notification::make()
                        ->title('تم حفظ توزيع الدرجات بنجاح')
                        ->success()
                        ->send();
                }),

            Action::make('upload_paper_sheet')
                ->label($this->record->paper_sheet_path ? 'تحديث ورقة الدرجات' : 'رفع ورقة الدرجات')
                ->icon(Heroicon::ArrowUpTray)
                ->color('info')
                ->modalHeading('رفع ورقة الدرجات (Hard Copy)')
                ->form([
                    FileUpload::make('paper_sheet_path')
                        ->label('ورقة الدرجات (صورة أو PDF)')
                        ->directory('grades/sheets')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                        ->maxSize(5120)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->record->update(['paper_sheet_path' => $data['paper_sheet_path']]);

                    Notification::make()
                        ->title('تم رفع ورقة الدرجات بنجاح')
                        ->success()
                        ->send();
                }),

            Action::make('submit_grades')
                ->label('إرسال الدرجات للاعتماد')
                ->icon(Heroicon::PaperAirplane)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('إرسال الدرجات للاعتماد')
                ->modalDescription('سيتم إرسال جميع الدرجات المحفوظة (المسودة) إلى الإدارة للمراجعة والاعتماد. لا يمكن تعديلها بعد الإرسال.')
                ->modalSubmitActionLabel('إرسال الآن')
                ->action(function (): void {
                    $count = Grade::whereHas('enrollment', fn (Builder $q) => $q->where('course_offering_id', $this->record->id))
                        ->where('status', GradeStatus::Draft)
                        ->update(['status' => GradeStatus::PendingApproval]);

                    Notification::make()
                        ->title("تم إرسال {$count} درجة للاعتماد")
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
                    ->visible(fn (StudentEnrollment $record): bool => $record->grade?->status !== GradeStatus::Approved
                        && $record->grade?->status !== GradeStatus::PendingApproval)
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
                            ->numeric()
                            ->minValue(0)
                            ->maxValue($this->record->final_max)
                            ->step(0.5)
                            ->required(),
                    ])
                    ->fillForm(fn (StudentEnrollment $record): array => [
                        'coursework_grade' => $record->grade?->coursework_grade,
                        'final_grade' => $record->grade?->final_grade,
                    ])
                    ->action(function (StudentEnrollment $record, array $data): void {
                        Grade::updateOrCreate(
                            ['enrollment_id' => $record->id],
                            [
                                'coursework_grade' => $data['coursework_grade'],
                                'final_grade' => $data['final_grade'],
                                'status' => GradeStatus::Draft,
                            ]
                        );

                        Notification::make()
                            ->title('تم حفظ الدرجة كمسودة')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
