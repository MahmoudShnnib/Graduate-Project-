<?php

namespace App\Filament\Resources\SuspensionRequests;

use App\Actions\Enrollment\DropStudentFromOffering;
use App\Enums\EnrollmentStatus;
use App\Enums\SuspensionStatus;
use App\Filament\Resources\SuspensionRequests\Pages\ListSuspensionRequests;
use App\Models\SuspensionRequest;
use App\Services\AuditService;
use App\Services\NotificationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SuspensionRequestResource extends Resource
{
    protected static ?string $model = SuspensionRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPauseCircle;

    protected static ?string $navigationLabel = 'طلبات إيقاف قيد';

    protected static ?string $modelLabel = 'طلب إيقاف قيد';

    protected static ?string $pluralModelLabel = 'طلبات إيقاف قيد';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة الطلاب';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.user.name')
                    ->label('الطالب')
                    ->searchable(),

                TextColumn::make('student.enrollment_no')
                    ->label('رقم القيد'),

                TextColumn::make('semester.name')
                    ->label('الفصل'),

                TextColumn::make('reason')
                    ->label('السبب')
                    ->limit(50),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (SuspensionStatus $state): string => $state->label())
                    ->color(fn (SuspensionStatus $state): string => match ($state) {
                        SuspensionStatus::Pending => 'warning',
                        SuspensionStatus::Approved => 'success',
                        SuspensionStatus::Rejected => 'danger',
                    }),

                TextColumn::make('created_at')
                    ->label('تاريخ الطلب')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(SuspensionStatus::class),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('قبول')
                    ->icon(Heroicon::Check)
                    ->color('success')
                    ->visible(fn (SuspensionRequest $record) => $record->status === SuspensionStatus::Pending)
                    ->requiresConfirmation()
                    ->modalDescription('سيتم سحب تسجيلات الطالب في هذا الفصل فقط، وستبقى حالته نشطة للفصول الأخرى.')
                    ->action(function (SuspensionRequest $record, AuditService $audit, NotificationService $notifier, DropStudentFromOffering $drop) {
                        $student = $record->student;
                        $semester = $record->semester;

                        $record->update([
                            'status' => SuspensionStatus::Approved,
                            'decided_by' => auth()->id(),
                            'decided_at' => now(),
                        ]);

                        // Semester-scoped cascade: withdraw only the enrollments
                        // in the suspended semester. Student.status stays Active
                        // so other semesters are unaffected. Using Withdrawn
                        // (not Dropped) to distinguish "forced by suspension"
                        // from "student-initiated drop".
                        $activeInSemester = $student->activeEnrollments()
                            ->whereHas('courseOffering', fn ($q) => $q->where('semester_id', $semester->id))
                            ->with('courseOffering')
                            ->get();

                        $withdrawn = 0;
                        foreach ($activeInSemester as $enrollment) {
                            if ($drop($student, $enrollment->courseOffering, EnrollmentStatus::Withdrawn)) {
                                $withdrawn++;
                            }
                        }

                        $audit->log(
                            'suspension_approved',
                            $record,
                            ['status' => SuspensionStatus::Pending->value],
                            ['status' => SuspensionStatus::Approved->value, 'enrollments_withdrawn' => $withdrawn],
                        );
                        $notifier->notifySuspensionDecision($record->fresh());

                        Notification::make()
                            ->title('تم قبول طلب إيقاف القيد')
                            ->body("تم سحب {$withdrawn} تسجيل في فصل {$semester->name}.")
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('رفض')
                    ->icon(Heroicon::XMark)
                    ->color('danger')
                    ->visible(fn (SuspensionRequest $record) => $record->status === SuspensionStatus::Pending)
                    ->form([
                        Textarea::make('decision_notes')
                            ->label('سبب الرفض')
                            ->required(),
                    ])
                    ->action(function (SuspensionRequest $record, array $data, NotificationService $notifier) {
                        $record->update([
                            'status' => SuspensionStatus::Rejected,
                            'decided_by' => auth()->id(),
                            'decided_at' => now(),
                            'decision_notes' => $data['decision_notes'],
                        ]);

                        $notifier->notifySuspensionDecision($record->fresh());

                        Notification::make()->title('تم رفض الطلب')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSuspensionRequests::route('/'),
        ];
    }
}
