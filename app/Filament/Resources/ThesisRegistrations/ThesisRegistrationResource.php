<?php

namespace App\Filament\Resources\ThesisRegistrations;

use App\Filament\Resources\ThesisRegistrations\Pages\ListThesisRegistrations;
use App\Models\ThesisRegistration;
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

class ThesisRegistrationResource extends Resource
{
    protected static ?string $model = ThesisRegistration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'تسجيلات الرسائل';

    protected static ?string $modelLabel = 'تسجيل رسالة';

    protected static ?string $pluralModelLabel = 'تسجيلات الرسائل';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة الطلاب';

    protected static ?int $navigationSort = 5;

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

                TextColumn::make('student.gpa')
                    ->label('المعدل')
                    ->numeric(2),

                TextColumn::make('title')
                    ->label('عنوان الرسالة')
                    ->limit(40)
                    ->searchable(),

                TextColumn::make('supervisor_name')
                    ->label('المشرف'),

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
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'قيد المراجعة',
                        'approved' => 'مقبول',
                        'rejected' => 'مرفوض',
                    ]),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('قبول')
                    ->icon(Heroicon::Check)
                    ->color('success')
                    ->visible(fn (ThesisRegistration $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (ThesisRegistration $record, AuditService $audit, NotificationService $notifier) {
                        $student = $record->student;

                        if (! $student->isEligibleForThesis()) {
                            Notification::make()
                                ->title('الطالب غير مؤهل')
                                ->body('يجب أن يكمل 10 مقررات على الأقل بمعدل 3.0 أو أعلى')
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->update([
                            'status' => 'approved',
                            'decided_by' => auth()->id(),
                            'decided_at' => now(),
                        ]);

                        $audit->log('thesis_approved', $record, ['status' => 'pending'], ['status' => 'approved']);
                        $notifier->notifyStudent($student, 'thesis_decision', 'تم قبول تسجيل رسالتك');

                        Notification::make()->title('تم قبول تسجيل الرسالة')->success()->send();
                    }),

                Action::make('reject')
                    ->label('رفض')
                    ->icon(Heroicon::XMark)
                    ->color('danger')
                    ->visible(fn (ThesisRegistration $record) => $record->status === 'pending')
                    ->form([
                        Textarea::make('decision_notes')
                            ->label('سبب الرفض')
                            ->required(),
                    ])
                    ->action(function (ThesisRegistration $record, array $data, NotificationService $notifier) {
                        $record->update([
                            'status' => 'rejected',
                            'decided_by' => auth()->id(),
                            'decided_at' => now(),
                        ]);

                        $notifier->notifyStudent($record->student, 'thesis_decision', 'تم رفض تسجيل رسالتك');

                        Notification::make()->title('تم رفض الطلب')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListThesisRegistrations::route('/'),
        ];
    }
}
