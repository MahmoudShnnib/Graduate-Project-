<?php

namespace App\Filament\Resources\Students;

use App\Enums\StudentStatus;
use App\Filament\Resources\Students\Pages\CreateStudent;
use App\Filament\Resources\Students\Pages\EditStudent;
use App\Filament\Resources\Students\Pages\ListStudents;
use App\Filament\Resources\Students\Pages\ViewStudent;
use App\Models\Course;
use App\Models\Specialization;
use App\Models\Student;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'specialization']);
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::User;

    protected static ?string $navigationLabel = 'الطلاب';

    protected static string|\UnitEnum|null $navigationGroup = 'شؤون الطلاب';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'طالب';

    protected static ?string $pluralModelLabel = 'الطلاب';

    protected static ?string $recordTitleAttribute = 'enrollment_no';

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                // Right column (RTL): Personal data + Degree document stacked.
                Grid::make(1)
                    ->columnSpan(1)
                    ->components([
                        Section::make('البيانات الشخصية')
                            ->extraAttributes(['class' => 'fsv-card fsv-card-personal'])
                            ->components([
                                TextEntry::make('last_name')
                                    ->label('اللقب')
                                    ->icon(Heroicon::User)
                                    ->inlineLabel()
                                    ->placeholder('-'),

                                TextEntry::make('mother_name')
                                    ->label('اسم الأم')
                                    ->icon(Heroicon::Users)
                                    ->inlineLabel()
                                    ->placeholder('-'),

                                TextEntry::make('birth_date')
                                    ->label('تاريخ الميلاد')
                                    ->icon(Heroicon::Calendar)
                                    ->inlineLabel()
                                    ->date()
                                    ->placeholder('-'),

                                TextEntry::make('registration_semester')
                                    ->label('الفصل الدراسي للالتحاق')
                                    ->icon(Heroicon::AcademicCap)
                                    ->inlineLabel()
                                    ->state(fn ($record): ?string => $record->registration_semester?->name)
                                    ->placeholder('-'),

                                TextEntry::make('birth_place')
                                    ->label('مكان الميلاد')
                                    ->icon(Heroicon::MapPin)
                                    ->inlineLabel()
                                    ->placeholder('-'),

                                TextEntry::make('gender')
                                    ->label('الجنس')
                                    ->icon(Heroicon::Identification)
                                    ->inlineLabel()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'male' => 'ذكر',
                                        'female' => 'أنثى',
                                        default => '-',
                                    })
                                    ->placeholder('-'),

                                TextEntry::make('marital_status')
                                    ->label('الحالة الاجتماعية')
                                    ->icon(Heroicon::Heart)
                                    ->inlineLabel()
                                    ->formatStateUsing(function (?string $state, $record): string {
                                        $female = ($record->gender ?? null) === 'female';

                                        return match ($state) {
                                            'single' => $female ? 'عزباء' : 'أعزب',
                                            'married' => $female ? 'متزوجة' : 'متزوج',
                                            'divorced' => $female ? 'مطلقة' : 'مطلق',
                                            'widowed' => $female ? 'أرملة' : 'أرمل',
                                            default => '-',
                                        };
                                    })
                                    ->placeholder('-'),

                                TextEntry::make('nationality')
                                    ->label('الجنسية')
                                    ->icon(Heroicon::Flag)
                                    ->inlineLabel()
                                    ->placeholder('-'),

                                TextEntry::make('card_number')
                                    ->label('رقم البطاقة')
                                    ->icon(Heroicon::Identification)
                                    ->inlineLabel()
                                    ->placeholder('-'),

                                TextEntry::make('profession')
                                    ->label('المهنة')
                                    ->icon(Heroicon::Briefcase)
                                    ->inlineLabel()
                                    ->placeholder('-'),
                            ]),

                        Section::make('الوثيقة العلمية')
                            ->extraAttributes(['class' => 'fsv-card fsv-card-degree'])
                            ->components([
                                \Filament\Schemas\Components\View::make('filament.resources.students.partials.degree-document'),
                            ]),
                    ]),

                // Left column (RTL): two stacked cards — Contact, then Study.
                Grid::make(1)
                    ->columnSpan(1)
                    ->components([
                        Section::make('بيانات التواصل والموقع')
                            ->extraAttributes(['class' => 'fsv-card fsv-card-contact'])
                            ->components([
                                TextEntry::make('user.email')
                                    ->label('البريد الإلكتروني')
                                    ->icon(Heroicon::Envelope)
                                    ->inlineLabel()
                                    ->weight('semibold')
                                    ->placeholder('-'),

                                TextEntry::make('user.phone')
                                    ->label('رقم النقال')
                                    ->icon(Heroicon::Phone)
                                    ->inlineLabel()
                                    ->weight('semibold')
                                    ->placeholder('-'),

                                TextEntry::make('permanent_address')
                                    ->label('العنوان الدائم')
                                    ->icon(Heroicon::MapPin)
                                    ->inlineLabel()
                                    ->placeholder('-'),

                                TextEntry::make('workplace')
                                    ->label('مكان العمل')
                                    ->icon(Heroicon::BuildingOffice)
                                    ->inlineLabel()
                                    ->placeholder('لا يوجد'),
                            ]),

                        Section::make('بيانات الدراسة')
                            ->extraAttributes(['class' => 'fsv-card fsv-card-study'])
                            ->components([
                                TextEntry::make('specialization.name')
                                    ->label('التخصص')
                                    ->icon(Heroicon::AcademicCap)
                                    ->inlineLabel()
                                    ->placeholder('-'),

                                TextEntry::make('study_type')
                                    ->label('نوع الدراسة')
                                    ->icon(Heroicon::DocumentText)
                                    ->inlineLabel()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'self_funded' => 'على حسابي الخاص',
                                        'internally_delegated' => 'موفد بقرار إيفاد داخلي',
                                        'faculty_assistant' => 'معيد بالكلية',
                                        'public_delegation' => 'موفد من جهة عامة',
                                        default => '-',
                                    })
                                    ->placeholder('-'),

                                TextEntry::make('delegation_number')
                                    ->label('رقم قرار الإيفاد')
                                    ->icon(Heroicon::Document)
                                    ->inlineLabel()
                                    ->placeholder('-'),

                                TextEntry::make('delegation_entity')
                                    ->label('جهة الإيفاد')
                                    ->icon(Heroicon::BuildingLibrary)
                                    ->inlineLabel()
                                    ->placeholder('-'),

                                // Transfer-student callout, only visible for transfer students.
                                TextEntry::make('is_transfer')
                                    ->label('')
                                    ->hiddenLabel()
                                    ->state('ملاحظة: منتقل من كلية أخرى')
                                    ->icon(Heroicon::InformationCircle)
                                    ->color('danger')
                                    ->extraAttributes(['class' => 'fsv-transfer-callout'])
                                    ->visible(fn (Student $record): bool => (bool) $record->is_transfer),
                            ]),
                    ]),

                // الوثيقة العلمية والمعدل rendered directly in the Blade view
                // for richer layout — see view-student.blade.php.

                Section::make('مستندات القبول')
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fsv-card fsv-card-docs'])
                    ->columns(2)
                    ->components([
                        TextEntry::make('bachelor_certificate')
                            ->label('الشهادة العلمية (بكالوريوس / دبلوم عالي)')
                            ->icon(Heroicon::DocumentText)
                            ->formatStateUsing(fn (?string $state): string => $state ? basename($state) : 'لم يُرفق')
                            ->url(fn (?string $state): ?string => $state ? asset('storage/'.$state) : null)
                            ->openUrlInNewTab(),

                        TextEntry::make('remedial_statement')
                            ->label('كشف المواد التكميلية')
                            ->icon(Heroicon::DocumentText)
                            ->formatStateUsing(fn (?string $state): string => $state ? basename($state) : 'لم يُرفق')
                            ->url(fn (?string $state): ?string => $state ? asset('storage/'.$state) : null)
                            ->openUrlInNewTab(),
                    ]),

                Section::make('مواد المعادلة')
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fsv-card fsv-card-transfer'])
                    ->hidden(fn (Student $record): bool => ! $record->is_transfer || $record->transferCourses()->doesntExist())
                    ->components([
                        RepeatableEntry::make('transferCourses')
                            ->label('')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('course.name')
                                    ->label('اسم المادة'),

                                TextEntry::make('coursework_grade')
                                    ->label('أعمال الفصل'),

                                TextEntry::make('final_grade')
                                    ->label('الامتحان النهائي'),

                                TextEntry::make('total')
                                    ->label('المجموع'),

                                TextEntry::make('letter')
                                    ->label('التقدير')
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'A' => 'success',
                                        'B+', 'B' => 'info',
                                        'C+', 'C' => 'warning',
                                        'D+', 'D' => 'gray',
                                        default => 'danger',
                                    }),

                                TextEntry::make('points')
                                    ->label('النقاط'),
                            ])
                            ->columns(6),
                    ]),
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([

            // Contact fields (email, phone) live on the related User row and are editable on
            // BOTH create and edit. Name + password are create-only (password is changed via
            // the WhatsApp self-service flows). Keeping one section avoids duplicate state
            // paths that two same-named fields would cause.
            Section::make('بيانات الحساب')
                ->columns(2)
                ->components([
                    TextInput::make('user_name')
                        ->label('الاسم الرباعي واللقب')
                        ->helperText('الاسم الأول + اسم الأب + اسم الجد + اسم العائلة (اللقب)')
                        ->required()
                        ->visibleOn('create')
                        ->rules(['string', 'max:255', new \App\Rules\ArabicOnly, new \App\Rules\MinArabicWords(4, 'يجب إدخال الاسم الرباعي كاملاً (4 أسماء على الأقل)')])
                        ->maxLength(255)
                        ->columnSpan(2),

                    TextInput::make('user_name_en')
                        ->label('الاسم بالإنجليزية (كما في جواز السفر)')
                        ->placeholder('Full name as in passport')
                        ->required()
                        ->visibleOn('create')
                        ->regex("/^[a-zA-Z\\s\\-']+$/")
                        ->maxLength(255)
                        ->columnSpan(2),

                    TextInput::make('user_email')
                        ->label('البريد الإلكتروني')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->rules([
                            fn (?\Filament\Resources\Pages\Page $livewire): string => $livewire instanceof \App\Filament\Resources\Students\Pages\EditStudent
                                ? 'unique:users,email,'.$livewire->getRecord()->user_id
                                : 'unique:users,email',
                        ])
                        ->validationMessages([
                            'unique' => 'هذا البريد الإلكتروني مستخدم بالفعل.',
                        ]),

                    TextInput::make('user_phone')
                        ->label('رقم النقال')
                        ->tel()
                        ->helperText('رقم ليبي يبدأ بـ 09 ويتكوّن من 10 أرقام (مثال: 0912345678)')
                        ->rules([
                            fn (): \Closure => function (string $attribute, mixed $value, \Closure $fail): void {
                                $v = trim((string) $value);
                                if ($v === '') {
                                    return;
                                }
                                if (! preg_match('/^\d+$/', $v)) {
                                    $fail('رقم النقال يتكون من أرقام فقط');
                                } elseif (! str_starts_with($v, '09')) {
                                    $fail('رقم النقال يبدأ بـ 09');
                                } elseif (strlen($v) !== 10) {
                                    $fail('رقم النقال يتكون من 10 أرقام (مثال: 0912345678)');
                                }
                            },
                        ])
                        ->maxLength(20),

                    TextInput::make('user_password')
                        ->label('كلمة المرور')
                        ->password()
                        ->visibleOn('create')
                        ->required()
                        ->minLength(8),
                ]),

            Section::make('البيانات الشخصية')
                ->columns(2)
                ->components([
                    Select::make('specialization_id')
                        ->relationship('specialization', 'name')
                        ->label('التخصص')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpan(2),

                    TextInput::make('mother_name')
                        ->label('اسم الأم')
                        ->required()
                        ->rules(['string', 'max:255', new \App\Rules\ArabicOnly, new \App\Rules\MinArabicWords(2, 'يرجى إدخال اسم الأم (كلمتين على الأقل)')])
                        ->maxLength(255),

                    DatePicker::make('birth_date')
                        ->label('تاريخ الميلاد')
                        ->required()
                        ->maxDate(now()->subYears(22))
                        ->minDate(now()->subYears(60))
                        ->helperText('يجب أن يكون عمر الطالب بين 22 و 60 سنة')
                        ->validationMessages([
                            'before_or_equal' => 'يجب ألا يقل عمر الطالب عن 22 سنة',
                            'after_or_equal' => 'يجب ألا يزيد عمر الطالب عن 60 سنة',
                        ]),

                    TextInput::make('birth_place')
                        ->label('مكان الميلاد')
                        ->required()
                        ->rules([new \App\Rules\ArabicOnly(allowNumbers: true)])
                        ->maxLength(255),

                    Select::make('gender')
                        ->label('الجنس')
                        ->options([
                            'male' => 'ذكر',
                            'female' => 'أنثى',
                        ])
                        ->required(),

                    Select::make('marital_status')
                        ->label('الحالة الاجتماعية')
                        ->options([
                            'single' => 'أعزب / عزباء',
                            'married' => 'متزوج / متزوجة',
                            'divorced' => 'مطلق / مطلقة',
                            'widowed' => 'أرمل / أرملة',
                        ])
                        ->required(),

                    Select::make('nationality')
                        ->label('الجنسية')
                        ->options(collect(config('nationalities.list'))->mapWithKeys(fn (array $c): array => [$c['name'] => $c['flag'].' '.$c['name']]))
                        ->default(config('nationalities.default'))
                        ->searchable()
                        ->required()
                        ->live(),

                    // Label + format mirror the public /apply form: Libyans enter a
                    // 12-digit national number; everyone else a 4–20 char ID / passport.
                    TextInput::make('national_id')
                        ->label(fn (Get $get): string => $get('nationality') === config('nationalities.default')
                            ? 'الرقم الوطني'
                            : 'رقم الهوية / جواز السفر')
                        ->helperText(fn (Get $get): string => $get('nationality') === config('nationalities.default')
                            ? 'يتكوّن من 12 رقماً'
                            : 'رقم الهوية الوطنية أو جواز السفر (من 4 إلى 20 حرفاً/رقماً)')
                        ->maxLength(fn (Get $get): int => $get('nationality') === config('nationalities.default') ? 12 : 20)
                        ->required()
                        ->rules([
                            fn (Get $get): \Closure => function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                                $isLibyan = $get('nationality') === config('nationalities.default');
                                $v = trim((string) $value);

                                if ($isLibyan) {
                                    if (! preg_match('/^\d{12}$/', $v)) {
                                        $fail('الرقم الوطني يتكون من 12 رقماً بالضبط');
                                    }
                                } elseif (! preg_match('/^[A-Za-z0-9]{4,20}$/', $v)) {
                                    $fail('يرجى إدخال رقم الهوية أو جواز السفر (4 إلى 20 حرفاً/رقماً)');
                                }
                            },
                        ])
                        ->unique(table: 'students', ignoreRecord: true)
                        ->validationMessages([
                            'unique' => 'هذا الرقم الوطني مسجل بالفعل لطالب آخر.',
                        ]),

                    TextInput::make('card_number')
                        ->label('رقم البطاقة'),

                    TextInput::make('residence')
                        ->label('مكان الإقامة')
                        ->required()
                        ->rules([new \App\Rules\ArabicOnly(allowNumbers: true)])
                        ->maxLength(255),

                    TextInput::make('permanent_address')
                        ->label('العنوان الدائم')
                        ->required()
                        ->rules([new \App\Rules\ArabicOnly(allowNumbers: true), 'max:500'])
                        ->maxLength(500)
                        ->columnSpan(2),

                    TextInput::make('profession')
                        ->label('المهنة')
                        ->rules(['nullable', new \App\Rules\ArabicOnly])
                        ->maxLength(255),

                    TextInput::make('workplace')
                        ->label('مكان العمل')
                        ->rules(['nullable', new \App\Rules\ArabicOnly(allowNumbers: true)])
                        ->maxLength(255),
                ]),

            Section::make('نوع الدراسة')
                ->columns(2)
                ->components([
                    Radio::make('study_type')
                        ->label('نوع الدراسة')
                        ->required()
                        ->live()
                        ->options([
                            'self_funded' => 'الدراسة على حسابي الخاص',
                            'internally_delegated' => 'موفد بموجب قرار إيفاد داخلي',
                            'faculty_assistant' => 'معيد بالكلية',
                            'public_delegation' => 'موفد من قِبَل جهة عامة',
                        ])
                        ->columnSpan(2),

                    TextInput::make('delegation_number')
                        ->label('رقم قرار الإيفاد')
                        ->columnSpan(2)
                        ->required(fn (Get $get): bool => $get('study_type') === 'internally_delegated')
                        ->visible(fn (Get $get): bool => $get('study_type') === 'internally_delegated'),

                    TextInput::make('delegation_entity')
                        ->label('جهة الإيفاد')
                        ->columnSpan(2)
                        ->required(fn (Get $get): bool => in_array($get('study_type'), ['faculty_assistant', 'public_delegation']))
                        ->visible(fn (Get $get): bool => in_array($get('study_type'), ['faculty_assistant', 'public_delegation'])),

                    Toggle::make('is_transfer')
                        ->label('منتقل من كلية أخرى؟')
                        ->columnSpan(2)
                        ->live(),

                    Actions::make([
                        Action::make('manage_transfer_courses')
                            ->label('معادلة مواد')
                            ->icon(Heroicon::AcademicCap)
                            ->color('warning')
                            ->slideOver()
                            ->modalHeading('إدخال مواد المعادلة')
                            ->modalDescription('أدخل المواد التي درسها الطالب في الكلية الأخرى مع درجاتها.')
                            ->form([
                                Select::make('specialization_filter')
                                    ->label('التخصص')
                                    ->options(fn () => Specialization::orderBy('name')->pluck('name', 'id'))
                                    ->placeholder('كل التخصصات')
                                    ->live()
                                    ->dehydrated(false),

                                Repeater::make('courses')
                                    ->label('المواد المعادلة')
                                    ->schema([
                                        Select::make('course_id')
                                            ->label('المادة')
                                            ->options(function (Get $get): array {
                                                $query = Course::query()->where('is_active', true);

                                                $specId = $get('../../specialization_filter');
                                                if ($specId) {
                                                    $query->whereHas('specializations', fn ($q) => $q->where('specializations.id', $specId));
                                                }

                                                $ownValue = $get('course_id');
                                                $takenIds = collect($get('../../courses') ?? [])
                                                    ->pluck('course_id')
                                                    ->filter(fn ($id) => $id && $id != $ownValue)
                                                    ->values()
                                                    ->all();

                                                if (! empty($takenIds)) {
                                                    $query->whereNotIn('id', $takenIds);
                                                }

                                                return $query->orderBy('name')->pluck('name', 'id')->toArray();
                                            })
                                            ->searchable()
                                            ->required()
                                            ->columnSpan(2),
                                        TextInput::make('coursework_grade')
                                            ->label('أعمال الفصل (من 40)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(40)
                                            ->required(),
                                        TextInput::make('final_grade')
                                            ->label('الامتحان النهائي (من 60)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(60)
                                            ->required(),
                                    ])
                                    ->columns(4)
                                    ->addActionLabel('إضافة مادة')
                                    ->defaultItems(0)
                                    ->reorderable(false),
                            ])
                            ->fillForm(fn (Get $schemaGet): array => [
                                'courses' => $schemaGet('transfer_courses') ?? [],
                            ])
                            ->action(fn (array $data, Set $schemaSet) => $schemaSet('transfer_courses', $data['courses']))
                            ->visible(fn (Get $get): bool => (bool) $get('is_transfer')),
                    ])->columnSpan(2),

                    Placeholder::make('transfer_courses_summary')
                        ->label('')
                        ->content(function (Get $get): string {
                            $courses = $get('transfer_courses') ?? [];

                            return count($courses) > 0
                                ? 'تم إضافة '.count($courses).' مادة للمعادلة'
                                : '';
                        })
                        ->columnSpan(2)
                        ->visible(fn (Get $get): bool => (bool) $get('is_transfer')),

                    \Filament\Forms\Components\Hidden::make('transfer_courses')
                        ->default([]),
                ]),

            Section::make('الوثيقة العلمية')
                ->components([
                    FileUpload::make('degree_image')
                        ->label('صورة الشهادة (JPG / PNG / PDF)')
                        ->disk('public')
                        ->directory('students/degrees')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->maxSize(5120),
                ]),

            Section::make('مستندات القبول')
                ->columns(2)
                ->components([
                    FileUpload::make('bachelor_certificate')
                        ->label('الشهادة العلمية (بكالوريوس / دبلوم عالي)')
                        ->disk('public')
                        ->directory('students/documents')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->maxSize(5120)
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->helperText('PDF أو صورة، بحد أقصى 5 ميجابايت'),

                    FileUpload::make('remedial_statement')
                        ->label('كشف المواد التكميلية')
                        ->disk('public')
                        ->directory('students/documents')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->maxSize(5120)
                        ->helperText('اختياري — إن وُجد'),
                ]),

            Section::make('الحالة الإدارية')
                ->columns(2)
                ->components([
                    Select::make('status')
                        ->label('الحالة')
                        ->options(StudentStatus::class)
                        ->default(StudentStatus::Active)
                        ->required()
                        ->searchable()
                        ->preload()
                        ->disabledOn('edit')
                        ->dehydratedWhenHidden()
                        ->helperText(fn (string $operation): ?string => $operation === 'edit'
                            ? 'لتغيير الحالة، استخدم زر "تغيير الحالة" في أعلى الصفحة.'
                            : null),

                    TextInput::make('enrollment_no')
                        ->label('رقم القيد')
                        ->disabled()
                        ->placeholder('سيتم توليد رقم القيد تلقائياً')
                        ->dehydrated(),

                    TextInput::make('warnings_count')
                        ->label('عدد الإنذارات')
                        ->numeric()
                        ->default(0)
                        ->disabled()
                        ->dehydrated(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('enrollment_no')
                    ->label('رقم القيد')
                    ->searchable(),

                TextColumn::make('user.name')
                    ->label('اسم الطالب')
                    ->searchable(),

                TextColumn::make('specialization.name')
                    ->label('التخصص'),

                IconColumn::make('is_transfer')
                    ->label('منتقل')
                    ->boolean()
                    ->toggleable(),

                TextColumn::make('gpa')
                    ->label('المعدل')
                    ->badge()
                    ->color(fn (string $state): string => (float) $state >= 3.0 ? 'success' : 'danger'),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->state(fn (Student $record): string => $record->getDisplayStatusLabel())
                    ->color(fn (Student $record): string => $record->getDisplayStatusColor()),

                TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime('Y/m/d')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(StudentStatus::class),
                SelectFilter::make('specialization_id')
                    ->relationship('specialization', 'name')
                    ->label('التخصص'),
                TernaryFilter::make('is_transfer')
                    ->label('منتقل من كلية أخرى')
                    ->trueLabel('نعم')
                    ->falseLabel('لا'),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStudents::route('/'),
            'create' => CreateStudent::route('/create'),
            'view' => ViewStudent::route('/{record}'),
            'edit' => EditStudent::route('/{record}/edit'),
        ];
    }
}
