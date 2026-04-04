<?php

namespace App\Filament\Resources\AddDropRequests;

use App\Enums\AddDropType;
use App\Enums\EnrollmentStatus;
use App\Filament\Resources\AddDropRequests\Pages\ListAddDropRequests;
use App\Models\AddDropRequest;
use App\Models\Semester;
use App\Models\StudentEnrollment;
use App\Services\NotificationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AddDropRequestResource extends Resource
{
    protected static ?string $model = AddDropRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?string $navigationLabel = 'طلبات الإضافة/الحذف';

    protected static ?string $modelLabel = 'طلب إضافة/حذف';

    protected static ?string $pluralModelLabel = 'طلبات الإضافة/الحذف';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة الطلاب';

    protected static ?int $navigationSort = 6;

    public static function shouldRegisterNavigation(): bool
    {
        $activeSemester = Semester::where('is_active', true)->first();

        return $activeSemester?->isAddDropOpen() ?? false;
    }

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

                TextColumn::make('courseOffering.course.name')
                    ->label('المقرر'),

                TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn (AddDropType $state): string => $state->label())
                    ->color(fn (AddDropType $state): string => match ($state) {
                        AddDropType::Add => 'success',
                        AddDropType::Drop => 'danger',
                    }),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'قيد المراجعة',
                        'approved' => 'مقبول',
                        'rejected' => 'مرفوض',
                        default => $state,
                    }),

                TextColumn::make('courseOffering.semester.name')
                    ->label('الفصل'),

                TextColumn::make('created_at')
                    ->label('تاريخ الطلب')
                    ->date()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('قبول')
                    ->icon(Heroicon::Check)
                    ->color('success')
                    ->visible(fn (AddDropRequest $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (AddDropRequest $record, NotificationService $notifier) {
                        $student = $record->student;
                        $offering = $record->courseOffering;

                        if ($record->type === AddDropType::Add) {
                            StudentEnrollment::firstOrCreate([
                                'student_id' => $student->id,
                                'course_offering_id' => $offering->id,
                            ], ['status' => EnrollmentStatus::Enrolled]);
                        } else {
                            StudentEnrollment::where([
                                'student_id' => $student->id,
                                'course_offering_id' => $offering->id,
                            ])->update(['status' => EnrollmentStatus::Dropped]);
                        }

                        $record->update([
                            'status' => 'approved',
                            'decided_by' => auth()->id(),
                            'decided_at' => now(),
                        ]);

                        $notifier->notifyStudent($student, 'add_drop_decision', 'تم قبول طلب الإضافة/الحذف الخاص بك');
                        Notification::make()->title('تم قبول الطلب')->success()->send();
                    }),

                Action::make('reject')
                    ->label('رفض')
                    ->icon(Heroicon::XMark)
                    ->color('danger')
                    ->visible(fn (AddDropRequest $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (AddDropRequest $record, NotificationService $notifier) {
                        $record->update([
                            'status' => 'rejected',
                            'decided_by' => auth()->id(),
                            'decided_at' => now(),
                        ]);

                        $notifier->notifyStudent($record->student, 'add_drop_decision', 'تم رفض طلب الإضافة/الحذف الخاص بك');
                        Notification::make()->title('تم رفض الطلب')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAddDropRequests::route('/'),
        ];
    }
}
