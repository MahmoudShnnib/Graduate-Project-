<?php

namespace App\Filament\Resources\ThesisRegistrations;

use App\Enums\ThesisStatus;
use App\Filament\Resources\ThesisRegistrations\Pages\ListThesisRegistrations;
use App\Filament\Resources\ThesisRegistrations\Pages\ViewThesisRegistration;
use App\Models\ThesisRegistration;
use App\Services\AuditService;
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
use Illuminate\Database\Eloquent\Builder;

class ThesisRegistrationResource extends Resource
{
    protected static ?string $model = ThesisRegistration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'تسجيلات الرسائل';

    protected static ?string $modelLabel = 'تسجيل رسالة';

    protected static ?string $pluralModelLabel = 'تسجيلات الرسائل';

    protected static string|\UnitEnum|null $navigationGroup = 'المناقشة والتخرج';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) ThesisRegistration::where('status', ThesisStatus::Pending)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        $latestIds = ThesisRegistration::query()
            ->selectRaw('MAX(id) as id')
            ->groupBy('student_id')
            ->pluck('id');

        return parent::getEloquentQuery()
            ->whereIn('id', $latestIds)
            ->with([
                'student.user',
                'supervisor',
                'facultySupervisor',
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (ThesisRegistration $record): string => static::getUrl('view', ['record' => $record]))
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

                TextColumn::make('supervisor_display')
                    ->label('المشرف')
                    ->state(fn (ThesisRegistration $record): string => $record->getSupervisorDisplayName()),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (ThesisStatus $state): string => $state->getColor())
                    ->formatStateUsing(fn (ThesisStatus $state): string => $state->getLabel()),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(ThesisStatus::class),
            ])
            ->recordActions([
                Action::make('print_for_ministry')
                    ->label('طباعة للوزارة')
                    ->icon(Heroicon::OutlinedPrinter)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('طباعة للوزارة')
                    ->modalDescription(fn (ThesisRegistration $record) => $record->status === ThesisStatus::Pending
                        ? 'سيتم تغيير حالة الطلب إلى "في انتظار الوزارة". هل تريد المتابعة؟'
                        : 'تم طباعة هذا الطلب من قبل. هل تريد إعادة الطباعة؟')
                    ->modalSubmitActionLabel('تأكيد الطباعة')
                    ->action(function (ThesisRegistration $record, AuditService $audit) {
                        if ($record->status === ThesisStatus::Pending) {
                            $record->update([
                                'status' => ThesisStatus::Printed,
                                'printed_at' => now(),
                            ]);

                            $audit->log('thesis_printed', $record);
                        }

                        return redirect()->route('print.thesis-ministry-form', $record);
                    }),

                Action::make('approve')
                    ->label('قبول')
                    ->icon(Heroicon::Check)
                    ->color('success')
                    ->visible(fn (ThesisRegistration $record) => $record->status === ThesisStatus::Printed)
                    ->requiresConfirmation()
                    ->modalHeading('قبول طلب تسجيل الرسالة')
                    ->modalDescription('سيتم قبول تسجيل الرسالة لهذا الطالب. هل أنت متأكد؟')
                    ->modalSubmitActionLabel('تأكيد القبول')
                    ->action(function (ThesisRegistration $record, AuditService $audit, \Livewire\Component $livewire) {
                        $student = $record->student;

                        if (! $student->isEligibleForThesis()) {
                            Notification::make()
                                ->title('الطالب غير مؤهل')
                                ->body('يجب إتمام جميع المقررات العامة والتخصصية ومقررتين اختياريتين بمعدل 3.0 أو أعلى')
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->update([
                            'status' => ThesisStatus::Approved,
                            'decided_by' => auth()->id(),
                            'decided_at' => now(),
                        ]);

                        $audit->log('thesis_approved', $record, ['status' => 'printed'], ['status' => 'approved']);

                        Notification::make()->title('تم قبول تسجيل الرسالة')->success()->send();

                        $livewire->dispatch('refresh-sidebar');
                    }),

                Action::make('reject')
                    ->label('رفض')
                    ->icon(Heroicon::XMark)
                    ->color('danger')
                    ->visible(fn (ThesisRegistration $record) => $record->status === ThesisStatus::Printed)
                    ->requiresConfirmation()
                    ->modalHeading('رفض طلب تسجيل الرسالة')
                    ->modalDescription('يرجى كتابة سبب الرفض. سيظهر للطالب ويمكنه إعادة التقديم.')
                    ->modalSubmitActionLabel('تأكيد الرفض')
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('سبب الرفض')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (ThesisRegistration $record, array $data, AuditService $audit, \Livewire\Component $livewire) {
                        $record->update([
                            'status' => ThesisStatus::Rejected,
                            'rejection_reason' => $data['rejection_reason'],
                            'decided_by' => auth()->id(),
                            'decided_at' => now(),
                        ]);

                        $audit->log('thesis_rejected', $record);

                        Notification::make()->title('تم رفض الطلب')->success()->send();

                        $livewire->dispatch('refresh-sidebar');
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListThesisRegistrations::route('/'),
            'view' => ViewThesisRegistration::route('/{record}'),
        ];
    }
}
