<?php

namespace App\Filament\Resources\Grades\Pages;

use App\Enums\GradeStatus;
use App\Filament\Resources\Grades\GradeResource;
use App\Models\CourseOffering;
use App\Models\Grade;
use App\Services\GradeCalculationService;
use App\Services\NotificationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class ViewCourseGrades extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = GradeResource::class;

    protected string $view = 'filament.resources.grades.pages.view-course-grades';

    public CourseOffering $record;

    public function getTitle(): string
    {
        return 'درجات مادة: '.$this->record->course->name;
    }

    public function getBreadcrumb(): string
    {
        return $this->record->course->name;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Grade::query()
                    ->whereHas('enrollment', fn ($q) => $q->where('course_offering_id', $this->record->id))
                    ->with(['enrollment.student.user'])
            )
            ->columns([
                TextColumn::make('enrollment.student.enrollment_no')
                    ->label('رقم القيد')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('enrollment.student.user.name')
                    ->label('اسم الطالب')
                    ->searchable(),

                TextColumn::make('coursework_grade')
                    ->label('أعمال الفصل')
                    ->numeric(2)
                    ->placeholder('—'),

                TextColumn::make('final_grade')
                    ->label('الامتحان النهائي')
                    ->numeric(2)
                    ->placeholder('—'),

                TextColumn::make('total')
                    ->label('المجموع')
                    ->numeric(2)
                    ->placeholder('—'),

                TextColumn::make('letter')
                    ->label('التقدير')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'A' => 'success',
                        'B+', 'B' => 'info',
                        'C+', 'C' => 'warning',
                        'D+', 'D' => 'gray',
                        default => 'danger',
                    })
                    ->placeholder('—'),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (GradeStatus $state): string => $state->label())
                    ->color(fn (GradeStatus $state): string => match ($state) {
                        GradeStatus::Draft => 'gray',
                        GradeStatus::PendingApproval => 'warning',
                        GradeStatus::Approved => 'success',
                    }),
            ])
            ->recordActions([
                Action::make('review')
                    ->label('مراجعة واعتماد')
                    ->icon(Heroicon::CheckBadge)
                    ->color('success')
                    ->visible(fn (Grade $record): bool => $record->status === GradeStatus::PendingApproval)
                    ->modalHeading('مراجعة الدرجة واعتمادها')
                    ->form([
                        Placeholder::make('paper_sheet_preview')
                            ->label('ورقة الدرجات')
                            ->content(function (Grade $record): HtmlString|string {
                                if (! $record->paper_sheet_path) {
                                    return 'لا يوجد مرفق';
                                }

                                $url = asset('storage/'.$record->paper_sheet_path);
                                $name = basename($record->paper_sheet_path);

                                return new HtmlString(
                                    '<a href="'.$url.'" target="_blank" class="text-primary-600 hover:underline flex items-center gap-1">'.
                                    '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z"/></svg>'.
                                    ' '.$name.'</a>'
                                );
                            }),

                        TextInput::make('coursework_grade')
                            ->label('درجة أعمال الفصل')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(40)
                            ->required(),

                        TextInput::make('final_grade')
                            ->label('درجة الامتحان النهائي')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(60)
                            ->required(),
                    ])
                    ->fillForm(fn (Grade $record): array => [
                        'coursework_grade' => $record->coursework_grade,
                        'final_grade' => $record->final_grade,
                    ])
                    ->action(function (Grade $record, array $data, GradeCalculationService $calculator, NotificationService $notifier): void {
                        $record->update([
                            'coursework_grade' => $data['coursework_grade'],
                            'final_grade' => $data['final_grade'],
                            'status' => GradeStatus::Approved,
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);

                        $student = $record->enrollment->student;
                        $calculator->recalculateStudentGPA($student);
                        $notifier->notifyGradePublished($record);

                        Notification::make()->title('تم اعتماد الدرجة بنجاح')->success()->send();
                    }),
            ])
            ->emptyStateHeading('لا توجد درجات مدخلة')
            ->emptyStateDescription('لم يقم عضو هيئة التدريس بإدخال الدرجات بعد.');
    }
}
