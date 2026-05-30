<?php

namespace App\Filament\Resources\Applications;

use App\Actions\ApproveApplication;
use App\Enums\ApplicationStatus;
use App\Filament\Resources\Applications\Pages\EditApplication;
use App\Filament\Resources\Applications\Pages\ListApplications;
use App\Filament\Resources\Applications\Pages\ViewApplication;
use App\Filament\Resources\Applications\Schemas\ApplicationInfolist;
use App\Models\Application;
use App\Services\AuditService;
use App\Services\NotificationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static ?string $navigationLabel = 'التسجيل والقبول';

    protected static ?string $modelLabel = 'طلب';

    protected static ?string $pluralModelLabel = 'طلبات التسجيل';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationBadge(): ?string
    {
        return (string) Application::where('status', ApplicationStatus::Pending)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function infolist(Schema $schema): Schema
    {
        return ApplicationInfolist::configure($schema);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('البيانات الشخصية')
                ->columns(2)
                ->components([
                    TextInput::make('name')
                        ->label('الاسم الرباعي واللقب')
                        ->helperText('الاسم الأول + اسم الأب + اسم الجد + اسم العائلة (اللقب)')
                        ->required()
                        ->columnSpan(2),

                    TextInput::make('name_en')
                        ->label('الاسم بالإنجليزية (كما في جواز السفر)')
                        ->placeholder('Full name as in passport')
                        ->columnSpan(2),

                    TextInput::make('email')
                        ->label('البريد الإلكتروني')
                        ->email()
                        ->required(),

                    TextInput::make('phone')
                        ->label('رقم النقال')
                        ->required(),

                    Select::make('specialization_id')
                        ->relationship('specialization', 'name')
                        ->label('التخصص')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->columnSpan(2),

                    TextInput::make('last_name')
                        ->label('اللقب'),

                    TextInput::make('mother_name')
                        ->label('اسم الأم'),

                    TextInput::make('birth_date')
                        ->label('تاريخ الميلاد')
                        ->type('date'),

                    TextInput::make('birth_place')
                        ->label('مكان الميلاد'),

                    Select::make('gender')
                        ->label('الجنس')
                        ->options([
                            'male' => 'ذكر',
                            'female' => 'أنثى',
                        ]),

                    Select::make('marital_status')
                        ->label('الحالة الاجتماعية')
                        ->options([
                            'single' => 'أعزب / عزباء',
                            'married' => 'متزوج / متزوجة',
                            'divorced' => 'مطلق / مطلقة',
                            'widowed' => 'أرمل / أرملة',
                        ]),

                    TextInput::make('nationality')
                        ->label('الجنسية')
                        ->default('ليبية'),

                    TextInput::make('national_id')
                        ->label('الرقم الوطني'),

                    TextInput::make('card_number')
                        ->label('رقم البطاقة'),

                    TextInput::make('residence')
                        ->label('مكان الإقامة'),

                    TextInput::make('permanent_address')
                        ->label('العنوان الدائم'),

                    TextInput::make('profession')
                        ->label('المهنة'),

                    TextInput::make('workplace')
                        ->label('مكان العمل'),
                ]),

            Section::make('نوع الدراسة')
                ->columns(2)
                ->components([
                    Radio::make('study_type')
                        ->label('نوع الدراسة')
                        ->options([
                            'self_funded' => 'الدراسة على حسابي الخاص',
                            'internally_delegated' => 'موفد بموجب قرار إيفاد داخلي',
                            'faculty_assistant' => 'معيد بالكلية',
                            'public_delegation' => 'موفد من قِبَل جهة عامة',
                        ])
                        ->columnSpan(2),

                    TextInput::make('delegation_number')
                        ->label('رقم قرار الإيفاد')
                        ->visible(fn ($get): bool => $get('study_type') === 'internally_delegated'),

                    TextInput::make('delegation_entity')
                        ->label('جهة الإيفاد')
                        ->visible(fn ($get): bool => in_array($get('study_type'), ['faculty_assistant', 'public_delegation'])),

                    \Filament\Forms\Components\Toggle::make('is_transfer')
                        ->label('منتقل من كلية أخرى؟')
                        ->columnSpan(2),
                ]),

            Section::make('القرار الإداري')
                ->columns(2)
                ->components([
                    Select::make('status')
                        ->label('الحالة')
                        ->options(ApplicationStatus::class)
                        ->default(ApplicationStatus::Pending)
                        ->required(),

                    DateTimePicker::make('exam_date')
                        ->label('تاريخ الامتحان')
                        ->visible(fn ($get): bool => $get('status') === ApplicationStatus::InitiallyApproved->value),

                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->rows(3)
                        ->columnSpan(2),

                    Textarea::make('rejection_reason')
                        ->label('سبب الرفض')
                        ->rows(3)
                        ->columnSpan(2)
                        ->visible(fn ($get): bool => $get('status') === ApplicationStatus::Rejected->value),
                ]),

            Section::make('المستندات')
                ->columns(2)
                ->components([
                    FileUpload::make('bachelor_certificate')
                        ->label('شهادة البكالوريوس')
                        ->directory('applications')
                        ->acceptedFileTypes(['application/pdf', 'image/*']),

                    FileUpload::make('remedial_statement')
                        ->label('كشف المواد التكميلية')
                        ->directory('applications')
                        ->acceptedFileTypes(['application/pdf', 'image/*']),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query, HasTable $livewire): void {
                if (property_exists($livewire, 'activeSpecialization') && $livewire->activeSpecialization) {
                    $query->where('specialization_id', $livewire->activeSpecialization);
                }

                // 3-tier status priority sort:
                //   0 = pending            → top (still needs review)
                //   1 = initially_approved → middle (reviewed, awaiting exam)
                //   2 = accepted/rejected  → bottom (done)
                // Applied here rather than via defaultSort so the grouping survives
                // user column-sort clicks. created_at desc is the tiebreaker within
                // each tier. reorder() drops any previously attached orderBy clauses
                // to guarantee this sort is the only one in play.
                $query
                    ->reorder()
                    ->orderByRaw("
                        CASE
                            WHEN status = 'pending' THEN 0
                            WHEN status = 'initially_approved' THEN 1
                            WHEN status IN ('accepted', 'rejected') THEN 2
                            ELSE 3
                        END ASC
                    ")
                    ->orderByDesc('created_at');
            })
            ->columns([
                TextColumn::make('tracking_code')
                    ->label('رقم التتبع')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('name')
                    ->label('الاسم الرباعي')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name_en')
                    ->label('الاسم بالإنجليزية')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('phone')
                    ->label('النقال')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('specialization.name')
                    ->label('التخصص')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (ApplicationStatus $state): string => match ($state) {
                        ApplicationStatus::Pending => 'warning',
                        ApplicationStatus::InitiallyApproved => 'info',
                        ApplicationStatus::Accepted => 'success',
                        ApplicationStatus::Rejected => 'danger',
                    })
                    ->formatStateUsing(fn (ApplicationStatus $state): string => $state->label()),

                TextColumn::make('created_at')
                    ->label('تاريخ التقديم')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('last_name')
                    ->label('اللقب')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('national_id')
                    ->label('الرقم الوطني')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('study_type')
                    ->label('نوع الدراسة')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'self_funded' => 'حساب خاص',
                        'internally_delegated' => 'إيفاد داخلي',
                        'faculty_assistant' => 'معيد',
                        'public_delegation' => 'جهة عامة',
                        default => '-',
                    })
                    ->placeholder('-')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(ApplicationStatus::class),

                SelectFilter::make('study_type')
                    ->label('نوع الدراسة')
                    ->options([
                        'self_funded' => 'حساب خاص',
                        'internally_delegated' => 'إيفاد داخلي',
                        'faculty_assistant' => 'معيد بالكلية',
                        'public_delegation' => 'جهة عامة',
                    ]),
            ])
            ->recordActions([
                static::initialApproveAction(),
                static::directApproveAction(),
                ViewAction::make(),
                DeleteAction::make()
                    ->modalDescription(fn (Application $record): string => "هل أنت متأكد من حذف طلب التقديم الخاص بـ {$record->name}؟ لا يمكن التراجع عن هذا الإجراء."),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkAction::make('bulkInitialApprove')
                    ->label('قبول مبدئي')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('قبول مبدئي للطلاب المحددين')
                    ->modalDescription('سيتم إضافة الطلاب المحددين إلى قائمة الامتحان التفاضلي.')
                    ->disabled(fn (\Livewire\Component $livewire): bool => ($livewire->scheduledExamDate ?? null) === null)
                    ->tooltip(fn (\Livewire\Component $livewire): ?string => ($livewire->scheduledExamDate ?? null)
                        ? null
                        : 'حدد موعد الامتحان أولاً')
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, \Livewire\Component $livewire) {
                        $examDate = $livewire->scheduledExamDate ?? null;

                        if (! $examDate) {
                            Notification::make()->title('يجب تحديد موعد الامتحان أولاً')->danger()->send();

                            return;
                        }

                        $notifier = app(NotificationService::class);
                        $count = 0;

                        foreach ($records as $application) {
                            if ($application->status !== ApplicationStatus::Pending) {
                                continue;
                            }

                            $application->update([
                                'status' => ApplicationStatus::InitiallyApproved,
                                'exam_date' => $examDate,
                            ]);

                            $notifier->notifyApplicationStatusChanged($application);
                            $count++;
                        }

                        Notification::make()
                            ->title("تم القبول المبدئي لـ {$count} طالب")
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApplications::route('/'),
            'view' => ViewApplication::route('/{record}'),
            'edit' => EditApplication::route('/{record}/edit'),
        ];
    }

    /**
     * Shared exam-date setter. Host page must declare a
     * `#[Session(key: 'applications.scheduled_exam_date')] public ?string $scheduledExamDate`
     * so list and view see the same value.
     */
    public static function scheduleExamAction(): Action
    {
        return Action::make('scheduleExam')
            ->label(fn (\Livewire\Component $livewire): string => ($livewire->scheduledExamDate ?? null)
                ? 'تعديل موعد الامتحان'
                : 'تحديد موعد الامتحان')
            ->icon('heroicon-o-calendar')
            ->color('info')
            ->modalHeading(fn (\Livewire\Component $livewire): string => ($livewire->scheduledExamDate ?? null)
                ? 'تعديل موعد الامتحان التفاضلي'
                : 'تحديد موعد الامتحان التفاضلي')
            ->modalDescription('سيتم عرض الموعد على الشاشة لاستخدامه عند القبول المبدئي.')
            ->modalSubmitActionLabel(fn (\Livewire\Component $livewire): string => ($livewire->scheduledExamDate ?? null)
                ? 'حفظ'
                : 'تحديد')
            ->fillForm(fn (\Livewire\Component $livewire): array => [
                'exam_date' => $livewire->scheduledExamDate ?? null,
            ])
            ->form([
                DateTimePicker::make('exam_date')
                    ->label('تاريخ الامتحان')
                    ->required(),
            ])
            ->action(function (array $data, \Livewire\Component $livewire): void {
                $wasSet = ($livewire->scheduledExamDate ?? null) !== null;
                $livewire->scheduledExamDate = $data['exam_date'];

                Notification::make()
                    ->title($wasSet ? 'تم تحديث موعد الامتحان' : 'تم تحديد موعد الامتحان')
                    ->success()
                    ->send();
            });
    }

    public static function clearExamDateAction(): Action
    {
        return Action::make('clearExamDate')
            ->label('إلغاء الموعد')
            ->icon('heroicon-o-x-mark')
            ->color('gray')
            ->requiresConfirmation()
            ->visible(fn (\Livewire\Component $livewire): bool => ($livewire->scheduledExamDate ?? null) !== null)
            ->action(function (\Livewire\Component $livewire): void {
                $livewire->scheduledExamDate = null;
            });
    }

    public static function initialApproveAction(): Action
    {
        return Action::make('initialApprove')
            ->label('قبول مبدئي')
            ->icon(Heroicon::ClipboardDocumentCheck)
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading(fn (Application $record): string => "قبول مبدئي — {$record->name}")
            ->modalDescription(fn (Application $record): string => "سيتم إضافة {$record->name} إلى قائمة الامتحان التفاضلي.")
            ->disabled(fn (\Livewire\Component $livewire): bool => ($livewire->scheduledExamDate ?? null) === null)
            ->tooltip(fn (\Livewire\Component $livewire): ?string => ($livewire->scheduledExamDate ?? null)
                ? null
                : 'حدد موعد الامتحان أولاً')
            ->action(function (Application $application, NotificationService $notifier, AuditService $audit, \Livewire\Component $livewire): void {
                $examDate = $livewire->scheduledExamDate ?? null;

                if (! $examDate) {
                    Notification::make()->title('يجب تحديد موعد الامتحان أولاً')->danger()->send();

                    return;
                }

                $application->update([
                    'status' => ApplicationStatus::InitiallyApproved,
                    'exam_date' => $examDate,
                ]);

                $audit->log('application_initial_approval', $application);
                $notifier->notifyApplicationStatusChanged($application);

                Notification::make()->title('تم القبول المبدئي بنجاح')->success()->send();
            })
            ->visible(fn (Application $record): bool => $record->status === ApplicationStatus::Pending);
    }

    public static function directApproveAction(): Action
    {
        return Action::make('approve')
            ->label('قبول مباشر')
            ->icon(Heroicon::Check)
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading(fn (Application $record): string => "قبول مباشر — {$record->name}")
            ->modalDescription(fn (Application $record): string => "سيتم قبول {$record->name} نهائياً وإنشاء سجل طالب بدون امتحان.")
            ->action(function (Application $application, ApproveApplication $approve, AuditService $audit): void {
                try {
                    $approve->handle($application);
                    $audit->log('application_approved', $application);
                    Notification::make()->title('تم القبول بنجاح')->success()->send();
                } catch (\Exception $e) {
                    Notification::make()->title('خطأ')->body($e->getMessage())->danger()->send();
                }
            })
            ->visible(fn (Application $record): bool => $record->status === ApplicationStatus::Pending);
    }
}
