<?php

namespace App\Filament\Resources\NotificationLogs;

use App\Filament\Resources\NotificationLogs\Pages\ListNotificationLogs;
use App\Models\NotificationLog;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
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
        'application_received' => 'استلام الطلب',
        'application_initially_approved' => 'قبول مبدئي',
        'application_exam_passed' => 'اجتياز الامتحان / قبول نهائي',
        'welcome_student' => 'ترحيب بالطالب الجديد',
        'grade_published' => 'نشر الدرجات',
        'thesis_decision' => 'قرار الرسالة العلمية',
        'suspension_decision' => 'قرار إيقاف القيد',
        'add_drop_decision' => 'قرار الإضافة/الحذف',
        'clearance_issued' => 'اعتماد الاستماحة',
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(NotificationLog::query()->with('student'))
            ->columns([
                TextColumn::make('student.name')
                    ->label('الطالب')
                    ->description(fn (NotificationLog $record): string => $record->student?->enrollment_no ?? '')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('event')
                    ->label('الحدث')
                    ->formatStateUsing(fn (string $state): string => self::EVENT_LABELS[$state] ?? $state),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sent' => 'success',
                        'failed' => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'sent' => 'تم الإرسال',
                        'failed' => 'فشل',
                        default => $state,
                    }),

                TextColumn::make('error_message')
                    ->label('سبب الفشل')
                    ->limit(50)
                    ->tooltip(fn (NotificationLog $record): ?string => $record->error_message)
                    ->placeholder('—')
                    ->color('danger'),

                TextColumn::make('sent_at')
                    ->label('وقت الإرسال')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'sent' => 'تم الإرسال',
                        'failed' => 'فشل',
                    ]),

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
