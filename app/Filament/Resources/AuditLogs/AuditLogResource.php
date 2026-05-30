<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\AuditLogs\Pages\ViewAuditLog;
use App\Models\AuditLog;
use App\Services\AuditService;
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
use Illuminate\Database\Eloquent\Builder;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'سجل التدقيق';

    protected static ?string $modelLabel = 'سجل';

    protected static ?string $pluralModelLabel = 'سجل التدقيق';

    protected static string|\UnitEnum|null $navigationGroup = 'النظام';

    protected static ?int $navigationSort = 99;

    public const MODEL_LABELS = [
        'App\\Models\\Grade' => 'درجة',
        'App\\Models\\Application' => 'طلب التحاق',
        'App\\Models\\Student' => 'طالب',
        'App\\Models\\StudentEnrollment' => 'تسجيل مقرر',
        'App\\Models\\ThesisRegistration' => 'تسجيل رسالة',
        'App\\Models\\CourseOffering' => 'مقرر مطروح',
        'App\\Models\\SuspensionRequest' => 'طلب إيقاف قيد',
        'App\\Models\\AddDropRequest' => 'طلب إضافة/حذف',
        'App\\Models\\DiscussionRequest' => 'طلب مناقشة',
        'App\\Models\\ExceptionalRequest' => 'فرصة استثنائية',
        'App\\Models\\ClearanceRequest' => 'استماحة',
        'App\\Models\\Semester' => 'فصل دراسي',
        'App\\Models\\CourseSurvey' => 'استطلاع مقررات',
    ];

    /**
     * Map `model_type` → admin URL pattern. `{id}` is replaced with `$record->model_id`.
     * Only listed types render the audit-log "Record ID" as a clickable drill-through.
     */
    public const MODEL_RESOURCE_MAP = [
        'App\\Models\\Application' => '/admin/applications/{id}',
        'App\\Models\\Student' => '/admin/students/{id}',
        'App\\Models\\ThesisRegistration' => '/admin/thesis-registrations/{id}',
        'App\\Models\\CourseOffering' => '/admin/course-offerings/{id}',
        'App\\Models\\SuspensionRequest' => '/admin/suspension-requests/{id}',
        'App\\Models\\DiscussionRequest' => '/admin/discussion-requests/{id}',
        'App\\Models\\Semester' => '/admin/semesters/{id}',
    ];

    public const ROLE_LABELS = [
        'super_admin' => 'مدير النظام',
        'secretary' => 'أمين السجل',
        'faculty' => 'عضو هيئة تدريس',
        'student' => 'طالب',
    ];

    public const FIELD_LABELS = [
        'coursework_grade' => 'درجة الأعمال',
        'final_grade' => 'الدرجة النهائية',
        'status' => 'الحالة',
        'midterm_published_at' => 'نشر أعمال الفصل',
        'published_at' => 'نشر الدرجات',
        'rejection_reason' => 'سبب الرفض',
        'warnings_count' => 'عدد التحذيرات',
        'enrollment_no' => 'رقم القيد',
        'gpa' => 'المعدل التراكمي',
        'letter' => 'التقدير',
        'total' => 'المجموع',
        'course_offering_id' => 'المقرر',
        'count' => 'العدد',
        'summon_message' => 'رسالة الاستدعاء',
        'discussion_date' => 'موعد المناقشة',
    ];

    public const STATUS_LABELS = [
        'draft' => 'مسودة',
        'pending_approval' => 'بانتظار الاعتماد',
        'approved' => 'معتمد',
        'active' => 'مستمر',
        'on_hold' => 'موقوف',
        'inactive' => 'منقطع',
        'transferred' => 'منتقل',
        'graduated' => 'متخرج',
        'dismissed' => 'مفصول',
        'pending' => 'معلق',
        'initially_approved' => 'قبول مبدئي',
        'accepted' => 'مقبول',
        'rejected' => 'مرفوض',
        'enrolled' => 'مسجل',
        'dropped' => 'محذوف',
        'withdrawn' => 'منسحب',
        'printed' => 'مطبوع',
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                AuditLog::query()
                    ->whereNotNull('user_id')
                    ->with('user')
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label('المستخدم')
                    ->description(fn (AuditLog $record): string => self::ROLE_LABELS[(string) $record->user?->role] ?? '')
                    ->placeholder('النظام')
                    ->searchable(),

                TextColumn::make('action')
                    ->label('الإجراء')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => AuditService::actionLabel($state))
                    ->color(fn (string $state): string => self::actionColor($state)),

                TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->since()
                    ->sortable(),

                TextColumn::make('old_values')
                    ->label('القيم القديمة')
                    ->html()
                    ->state(fn (AuditLog $record): string => self::formatValuesHtml((array) ($record->old_values ?? [])))
                    ->wrap(),

                TextColumn::make('new_values')
                    ->label('القيم الجديدة')
                    ->html()
                    ->state(fn (AuditLog $record): string => self::formatValuesHtml((array) ($record->new_values ?? [])))
                    ->wrap(),

                TextColumn::make('model_type')
                    ->label('النموذج')
                    ->formatStateUsing(fn (?string $state): string => $state
                        ? (self::MODEL_LABELS[$state] ?? class_basename($state))
                        : '—'),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('الإجراء')
                    ->multiple()
                    ->options(fn () => AuditLog::distinct()
                        ->pluck('action', 'action')
                        ->mapWithKeys(fn ($val, $key) => [$key => AuditService::actionLabel($key)])
                        ->toArray()),

                SelectFilter::make('user_id')
                    ->label('المستخدم')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('role')
                    ->label('الدور')
                    ->options(self::ROLE_LABELS)
                    ->query(fn (Builder $query, array $data): Builder => $data['value']
                        ? $query->whereHas('user', fn ($q) => $q->where('role', $data['value']))
                        : $query),

                SelectFilter::make('model_type')
                    ->label('النموذج')
                    ->options(fn () => AuditLog::distinct()
                        ->pluck('model_type', 'model_type')
                        ->mapWithKeys(fn ($val, $key) => [$key => self::MODEL_LABELS[$key] ?? class_basename($key)])
                        ->toArray()),

                Filter::make('date_range')
                    ->label('الفترة')
                    ->form([
                        DatePicker::make('from')->label('من'),
                        DatePicker::make('to')->label('إلى'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['to'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),

                Filter::make('show_system')
                    ->label('إظهار إجراءات النظام')
                    ->toggle()
                    ->query(fn (Builder $query, array $data): Builder => $data['isActive']
                        ? AuditLog::query()
                        : $query),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (AuditLog $record): string => static::getUrl('view', ['record' => $record->getKey()]))
            ->recordActions([
                Action::make('view')
                    ->label('عرض')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (AuditLog $record): string => static::getUrl('view', ['record' => $record->getKey()]))
                    ->color('gray'),

            ]);
    }

    private static function actionColor(string $action): string
    {
        return match (true) {
            str_contains($action, 'grade') || str_contains($action, 'Grade') => 'warning',
            str_contains($action, 'application') => 'success',
            str_contains($action, 'status') || str_contains($action, 'suspension') => 'danger',
            str_contains($action, 'discussion') || str_contains($action, 'thesis') => 'info',
            str_contains($action, 'enrolled') || str_contains($action, 'dropped') || str_contains($action, 'survey') => 'primary',
            default => 'gray',
        };
    }

    private static function formatValuesHtml(array $values): string
    {
        if (empty($values)) {
            return '<span class="text-xs text-gray-400 dark:text-gray-500">—</span>';
        }

        $parts = [];

        foreach ($values as $key => $value) {
            $fieldLabel = self::FIELD_LABELS[$key] ?? $key;
            $displayValue = self::formatFieldValue($key, $value);

            $parts[] = '<div class="leading-snug">'
                .'<span class="text-xs text-gray-500 dark:text-gray-400">'.e($fieldLabel).':</span> '
                .'<strong class="text-xs text-gray-800 dark:text-gray-200">'.e($displayValue).'</strong>'
                .'</div>';
        }

        return implode('', $parts);
    }

    public static function formatFieldValue(string $key, mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        if (str_ends_with($key, '_at') && $value) {
            try {
                return \Carbon\Carbon::parse((string) $value)->format('Y/m/d H:i');
            } catch (\Exception) {
                return (string) $value;
            }
        }

        return self::STATUS_LABELS[(string) $value] ?? (string) $value;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
            'view' => ViewAuditLog::route('/{record}'),
        ];
    }
}
