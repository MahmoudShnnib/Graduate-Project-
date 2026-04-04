<?php

namespace App\Filament\Resources\Grades;

use App\Enums\GradeStatus;
use App\Filament\Resources\Grades\Pages\ListGrades;
use App\Models\Grade;
use App\Services\GradeCalculationService;
use App\Services\NotificationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GradeResource extends Resource
{
    protected static ?string $model = Grade::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'الدرجات';

    protected static ?string $modelLabel = 'درجة';

    protected static ?string $pluralModelLabel = 'الدرجات';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة الطلاب';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('enrollment.student.user.name')
                    ->label('الطالب')
                    ->searchable(),

                TextColumn::make('enrollment.courseOffering.course.name')
                    ->label('المقرر'),

                TextColumn::make('enrollment.courseOffering.semester.name')
                    ->label('الفصل'),

                TextColumn::make('coursework_grade')
                    ->label('أعمال السنة')
                    ->numeric(2)
                    ->placeholder('-'),

                TextColumn::make('final_grade')
                    ->label('الامتحان النهائي')
                    ->numeric(2)
                    ->placeholder('-'),

                TextColumn::make('total')
                    ->label('المجموع')
                    ->numeric(2)
                    ->placeholder('-'),

                TextColumn::make('letter')
                    ->label('التقدير')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'A' => 'success',
                        'B+', 'B' => 'info',
                        'C+', 'C' => 'warning',
                        'D+', 'D' => 'gray',
                        'F' => 'danger',
                        default => 'gray',
                    })
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (GradeStatus $state): string => $state->label())
                    ->color(fn (GradeStatus $state): string => match ($state) {
                        GradeStatus::Draft => 'gray',
                        GradeStatus::PendingApproval => 'warning',
                        GradeStatus::Approved => 'success',
                    }),

                TextColumn::make('enrollment.courseOffering.faculty.name')
                    ->label('عضو هيئة التدريس')
                    ->placeholder('-'),

                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(GradeStatus::class),

                SelectFilter::make('semester')
                    ->label('الفصل')
                    ->relationship('enrollment.courseOffering.semester', 'name'),
            ])
            ->recordActions([
                Action::make('review')
                    ->label('مراجعة واعتماد')
                    ->icon(Heroicon::CheckBadge)
                    ->color('success')
                    ->visible(fn (Grade $record) => $record->status === GradeStatus::PendingApproval)
                    ->modalHeading('مراجعة الدرجة واعتمادها')
                    ->form([
                        Placeholder::make('paper_sheet_preview')
                            ->label('ورقة الدرجات')
                            ->content(fn (Grade $record) => $record->paper_sheet_path
                                ? '✓ مرفق: '.basename($record->paper_sheet_path)
                                : 'لا يوجد مرفق'),

                        TextInput::make('coursework_grade')
                            ->label('درجة أعمال السنة')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->required(),

                        TextInput::make('final_grade')
                            ->label('درجة الامتحان النهائي')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->required(),
                    ])
                    ->fillForm(fn (Grade $record): array => [
                        'coursework_grade' => $record->coursework_grade,
                        'final_grade' => $record->final_grade,
                    ])
                    ->action(function (Grade $record, array $data, GradeCalculationService $calculator, NotificationService $notifier) {
                        $record->update([
                            'coursework_grade' => $data['coursework_grade'],
                            'final_grade' => $data['final_grade'],
                            'status' => GradeStatus::Approved,
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);

                        $student = $record->enrollment->student;
                        $calculator->recalculateStudentGPA($student);
                        $notifier->notifyStudent($student, 'grades_published', 'تم نشر درجاتك وإعتمادها');

                        Notification::make()->title('تم اعتماد الدرجة بنجاح')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGrades::route('/'),
        ];
    }
}
