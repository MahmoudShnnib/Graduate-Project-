<?php

namespace App\Filament\Resources\SuspensionRequests;

use App\Enums\StudentStatus;
use App\Enums\SuspensionStatus;
use App\Filament\Resources\SuspensionRequests\Pages\ListSuspensionRequests;
use App\Filament\Resources\SuspensionRequests\Pages\ViewSuspensionRequest;
use App\Models\SuspensionRequest;
use App\Services\AuditService;
use App\Services\NotificationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SuspensionRequestResource extends Resource
{
    protected static ?string $model = SuspensionRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPauseCircle;

    protected static ?string $navigationLabel = 'طلبات إيقاف قيد';

    protected static ?string $modelLabel = 'طلب إيقاف قيد';

    protected static ?string $pluralModelLabel = 'طلبات إيقاف قيد';

    protected static string|\UnitEnum|null $navigationGroup = 'شؤون الطلاب';

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        return (string) SuspensionRequest::where('status', SuspensionStatus::Pending)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                // Right column (RTL): request details
                Grid::make(1)
                    ->columnSpan(1)
                    ->components([
                        Section::make('تفاصيل الطلب')
                            ->extraAttributes(['class' => 'fsv-card fsv-card-request'])
                            ->components([
                                TextEntry::make('student.user.name')
                                    ->label('الطالب')
                                    ->icon(Heroicon::User)
                                    ->inlineLabel()
                                    ->placeholder('—'),

                                TextEntry::make('student.enrollment_no')
                                    ->label('رقم القيد')
                                    ->icon(Heroicon::Hashtag)
                                    ->inlineLabel()
                                    ->placeholder('—'),

                                TextEntry::make('student.specialization.name')
                                    ->label('التخصص')
                                    ->icon(Heroicon::AcademicCap)
                                    ->inlineLabel()
                                    ->placeholder('—'),

                                TextEntry::make('semester.name')
                                    ->label('الفصل الدراسي')
                                    ->icon(Heroicon::CalendarDays)
                                    ->inlineLabel()
                                    ->placeholder('—'),

                                TextEntry::make('created_at')
                                    ->label('تاريخ التقديم')
                                    ->icon(Heroicon::Clock)
                                    ->inlineLabel()
                                    ->dateTime('Y/m/d H:i')
                                    ->placeholder('—'),

                                TextEntry::make('reason')
                                    ->label('سبب الإيقاف')
                                    ->icon(Heroicon::DocumentText)
                                    ->columnSpanFull()
                                    ->placeholder('—'),
                            ]),
                    ]),

                // Left column (RTL): decision details
                Grid::make(1)
                    ->columnSpan(1)
                    ->components([
                        Section::make('قرار الإدارة')
                            ->extraAttributes(['class' => 'fsv-card fsv-card-decision'])
                            ->components([
                                TextEntry::make('status')
                                    ->label('الحالة')
                                    ->icon(Heroicon::Flag)
                                    ->inlineLabel()
                                    ->formatStateUsing(fn (SuspensionStatus $state): string => $state->label())
                                    ->badge()
                                    ->color(fn (SuspensionStatus $state): string => match ($state) {
                                        SuspensionStatus::Pending => 'warning',
                                        SuspensionStatus::Approved => 'success',
                                        SuspensionStatus::Rejected => 'danger',
                                    }),

                                TextEntry::make('decidedBy.name')
                                    ->label('قرر بواسطة')
                                    ->icon(Heroicon::UserCircle)
                                    ->inlineLabel()
                                    ->placeholder('—')
                                    ->visible(fn (SuspensionRequest $record): bool => $record->decided_at !== null),

                                TextEntry::make('decided_at')
                                    ->label('تاريخ القرار')
                                    ->icon(Heroicon::CheckCircle)
                                    ->inlineLabel()
                                    ->dateTime('Y/m/d H:i')
                                    ->placeholder('—')
                                    ->visible(fn (SuspensionRequest $record): bool => $record->decided_at !== null),

                                TextEntry::make('decision_notes')
                                    ->label('ملاحظات القرار')
                                    ->icon(Heroicon::ChatBubbleLeftRight)
                                    ->columnSpanFull()
                                    ->placeholder('—')
                                    ->visible(fn (SuspensionRequest $record): bool => filled($record->decision_notes)),

                                TextEntry::make('pending_notice')
                                    ->hiddenLabel()
                                    ->state('هذا الطلب لا يزال قيد المراجعة. استخدم زر القبول أو الرفض في أعلى الصفحة لاتخاذ قرار.')
                                    ->icon(Heroicon::InformationCircle)
                                    ->columnSpanFull()
                                    ->extraAttributes(['class' => 'fsv-pending-callout'])
                                    ->visible(fn (SuspensionRequest $record): bool => $record->status === SuspensionStatus::Pending),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (SuspensionRequest $record): string => ViewSuspensionRequest::getUrl(['record' => $record]))
            ->modifyQueryUsing(function (Builder $query): void {
                $query
                    ->reorder()
                    ->orderByRaw("
                        CASE
                            WHEN status = 'pending' THEN 0
                            WHEN status IN ('approved', 'rejected') THEN 1
                            ELSE 2
                        END ASC
                    ")
                    ->orderByDesc('created_at');
            })
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
                    ->limit(60)
                    ->tooltip('انقر لعرض السبب كاملاً')
                    ->action(
                        Action::make('viewFullReason')
                            ->modalHeading('سبب الإيقاف الكامل')
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('إغلاق')
                            ->modalContent(fn (SuspensionRequest $record) => view(
                                'filament.modals.full-reason',
                                ['reason' => $record->reason]
                            ))
                    ),

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
                Action::make('view')
                    ->label('عرض')
                    ->icon(Heroicon::Eye)
                    ->color('gray')
                    ->url(fn (SuspensionRequest $record): string => ViewSuspensionRequest::getUrl(['record' => $record])),

                Action::make('approve')
                    ->label('قبول')
                    ->icon(Heroicon::Check)
                    ->color('success')
                    ->visible(fn (SuspensionRequest $record) => $record->status === SuspensionStatus::Pending)
                    ->requiresConfirmation()
                    ->modalHeading(fn (SuspensionRequest $record): string => 'قبول إيقاف قيد '.($record->student?->user?->name ?? ''))
                    ->modalDescription(fn (SuspensionRequest $record): string => 'سيتم سحب تسجيلات '.($record->student?->user?->name ?? 'الطالب').' في هذا الفصل فقط، وستبقى حالته نشطة للفصول الأخرى.')
                    ->action(function (SuspensionRequest $record, AuditService $audit, NotificationService $notifier) {
                        $student = $record->student;

                        $record->update([
                            'status' => SuspensionStatus::Approved,
                            'decided_by' => auth()->id(),
                            'decided_at' => now(),
                        ]);

                        $student->update(['status' => StudentStatus::OnHold]);

                        $audit->log(
                            'suspension_approved',
                            $record,
                            ['status' => SuspensionStatus::Pending->value],
                            ['status' => SuspensionStatus::Approved->value],
                        );
                        $notifier->notifySuspensionDecision($record->fresh());

                        Notification::make()
                            ->title('تم قبول طلب إيقاف القيد')
                            ->body('تم إيقاف قيد الطالب وسحب جميع تسجيلاته النشطة.')
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
                    ->action(function (SuspensionRequest $record, array $data, NotificationService $notifier, AuditService $audit) {
                        $record->update([
                            'status' => SuspensionStatus::Rejected,
                            'decided_by' => auth()->id(),
                            'decided_at' => now(),
                            'decision_notes' => $data['decision_notes'],
                        ]);

                        $audit->log('suspension_rejected', $record);
                        $notifier->notifySuspensionDecision($record->fresh());

                        Notification::make()->title('تم رفض الطلب')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSuspensionRequests::route('/'),
            'view' => ViewSuspensionRequest::route('/{record}'),
        ];
    }
}
