<?php

namespace App\Filament\Resources\NotificationLogs;

use App\Enums\NotificationChannel;
use App\Filament\Resources\NotificationLogs\Pages\ListNotificationLogs;
use App\Models\NotificationLog;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class NotificationLogResource extends Resource
{
    protected static ?string $model = NotificationLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static ?string $navigationLabel = 'سجلات الإشعارات';

    protected static ?string $modelLabel = 'إشعار';

    protected static ?string $pluralModelLabel = 'سجلات الإشعارات';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة الوصول والنظام';

    protected static ?int $navigationSort = 5;

    public const EVENT_LABELS = [
        'application_initially_approved' => 'قبول مبدئي',
        'welcome_student' => 'القبول النهائي والترحيب بالطالب',
        'grades_published' => 'نشر الدرجات',
        'contact_info_changed' => 'تغيير بيانات التواصل',
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    /** "3 مستلمين" — distinct recipients in a broadcast. */
    public static function batchRecipients(string $batchId): string
    {
        $count = NotificationLog::query()->where('batch_id', $batchId)->distinct()->count('phone');

        return trans_choice('{1} مستلم واحد|[2,*] :count مستلمين', $count, ['count' => $count]);
    }

    /** "✓ 118 · ✗ 2" — per-status delivery counts for a broadcast. */
    public static function batchStatusLine(string $batchId): string
    {
        $statuses = NotificationLog::query()->where('batch_id', $batchId)->pluck('status');

        $parts = ['✓ '.$statuses->filter(fn (string $s): bool => $s === 'sent')->count()];

        if ($failed = $statuses->filter(fn (string $s): bool => $s === 'failed')->count()) {
            $parts[] = '✗ '.$failed;
        }

        if ($pending = $statuses->filter(fn (string $s): bool => $s === 'pending')->count()) {
            $parts[] = '⏳ '.$pending;
        }

        return implode(' · ', $parts);
    }

    public static function table(Table $table): Table
    {
        return $table
            // Individual messages show as plain rows; a broadcast (e.g. publishing
            // grades to all students) collapses into ONE summary row (its first
            // message), expandable via the "عرض الرسائل" action.
            ->query(NotificationLog::query()
                ->with('student.user')
                ->where(fn (Builder $query): Builder => $query
                    ->whereNull('batch_id')
                    ->orWhereIn('id', fn ($sub) => $sub
                        ->from('notification_logs')
                        ->whereNotNull('batch_id')
                        ->groupBy('batch_id')
                        ->selectRaw('MIN(id)'))))
            ->paginationPageOptions([25, 50, 100])
            ->defaultPaginationPageOption(50)
            ->columns([
                TextColumn::make('student.user.name')
                    ->label('الطالب')
                    ->state(fn (NotificationLog $record): ?string => $record->batch_id
                        ? '📢 بث جماعي'
                        : $record->student?->user?->name)
                    ->description(fn (NotificationLog $record): ?string => $record->batch_id
                        ? self::batchRecipients($record->batch_id)
                        : ($record->student ? 'رقم القيد: '.($record->student->enrollment_no ?? '#'.$record->student->id) : null))
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('channel')
                    ->label('القناة')
                    ->state(fn (NotificationLog $record): ?NotificationChannel => $record->batch_id ? null : $record->channel)
                    ->badge()
                    ->icon(fn (?NotificationChannel $state): ?string => match ($state) {
                        NotificationChannel::Telegram => 'heroicon-o-paper-airplane',
                        NotificationChannel::Whatsapp => 'heroicon-o-chat-bubble-left-right',
                        NotificationChannel::Email => 'heroicon-o-envelope',
                        default => null,
                    })
                    ->color(fn (?NotificationChannel $state): string => match ($state) {
                        NotificationChannel::Telegram => 'info',
                        NotificationChannel::Whatsapp => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?NotificationChannel $state): ?string => $state?->label())
                    ->placeholder('—'),

                TextColumn::make('phone')
                    ->label('الرقم')
                    ->state(fn (NotificationLog $record): ?string => $record->batch_id ? null : $record->phone)
                    ->placeholder('—')
                    ->copyable()
                    ->searchable(),

                TextColumn::make('event')
                    ->label('الحدث')
                    ->formatStateUsing(fn (string $state): string => self::EVENT_LABELS[$state] ?? $state),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->state(fn (NotificationLog $record): string => $record->batch_id
                        ? self::batchStatusLine($record->batch_id)
                        : $record->status)
                    ->badge(fn (NotificationLog $record): bool => $record->batch_id === null)
                    ->color(fn (string $state, NotificationLog $record): string => $record->batch_id
                        ? (str_contains($state, '✗') ? 'danger' : 'success')
                        : match ($state) {
                            'sent' => 'success',
                            'failed' => 'danger',
                            default => 'warning',
                        })
                    ->formatStateUsing(fn (string $state, NotificationLog $record): string => $record->batch_id
                        ? $state
                        : match ($state) {
                            'sent' => 'تم الإرسال',
                            'failed' => 'فشل',
                            'pending' => 'قيد الإرسال',
                            default => $state,
                        }),

                TextColumn::make('error_message')
                    ->label('سبب الفشل')
                    ->state(fn (NotificationLog $record): ?string => $record->batch_id ? null : $record->error_message)
                    ->limit(22)
                    ->tooltip(fn (NotificationLog $record): ?string => $record->batch_id ? null : $record->error_message)
                    ->copyable()
                    ->copyMessage('تم نسخ سبب الفشل')
                    ->placeholder('—')
                    ->color('danger'),

                TextColumn::make('sent_at')
                    ->label('وقت الإرسال')
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('viewMessages')
                    ->label('عرض الرسائل')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->visible(fn (NotificationLog $record): bool => $record->batch_id !== null)
                    ->modalHeading(fn (NotificationLog $record): string => (self::EVENT_LABELS[$record->event] ?? $record->event).' — '.self::batchRecipients($record->batch_id))
                    ->modalContent(fn (NotificationLog $record): View => view('filament.notification-logs.batch-detail', [
                        'rows' => NotificationLog::query()
                            ->with('student.user')
                            ->where('batch_id', $record->batch_id)
                            ->orderByDesc('sent_at')
                            ->get(),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('إغلاق'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'sent' => 'تم الإرسال',
                        'failed' => 'فشل',
                        'pending' => 'قيد الإرسال',
                    ]),

                SelectFilter::make('channel')
                    ->label('القناة')
                    ->options(collect(NotificationChannel::cases())
                        ->mapWithKeys(fn (NotificationChannel $c): array => [$c->value => $c->label()])
                        ->all()),

                SelectFilter::make('event')
                    ->label('الحدث')
                    ->options(self::EVENT_LABELS),

                Filter::make('date_range')
                    ->label('الفترة')
                    ->form([
                        DatePicker::make('from')->label('من'),
                        DatePicker::make('to')->label('إلى'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('sent_at', '>=', $date))
                            ->when($data['to'], fn ($q, $date) => $q->whereDate('sent_at', '<=', $date));
                    }),
            ])
            ->defaultSort('sent_at', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotificationLogs::route('/'),
        ];
    }
}
