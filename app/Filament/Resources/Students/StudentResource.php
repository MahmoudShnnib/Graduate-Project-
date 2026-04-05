<?php

namespace App\Filament\Resources\Students;

use App\Enums\StudentStatus;
use App\Filament\Resources\Students\Pages\CreateStudent;
use App\Filament\Resources\Students\Pages\EditStudent;
use App\Filament\Resources\Students\Pages\ListStudents;
use App\Models\Student;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::User;

    protected static ?string $navigationLabel = 'الطلاب';

    protected static ?string $modelLabel = 'طالب';

    protected static ?string $pluralModelLabel = 'الطلاب';

    protected static ?string $recordTitleAttribute = 'enrollment_no';

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->compact()
                ->components([
                    Grid::make(4)
                        ->components([
                            TextEntry::make('enrollment_no')
                                ->label('رقم القيد')
                                ->placeholder('-'),

                            TextEntry::make('user.name')
                                ->label('الاسم الرباعي'),

                            TextEntry::make('last_name')
                                ->label('اللقب')
                                ->placeholder('-'),

                            TextEntry::make('status')
                                ->label('الحالة')
                                ->badge()
                                ->formatStateUsing(fn (StudentStatus $state): string => $state->label()),
                        ]),
                ]),

            Section::make('أولاً: البيانات الشخصية')
                ->columns(3)
                ->components([
                    TextEntry::make('mother_name')
                        ->label('اسم الأم')
                        ->placeholder('-'),

                    TextEntry::make('birth_date')
                        ->label('تاريخ الميلاد')
                        ->date()
                        ->placeholder('-'),

                    TextEntry::make('birth_place')
                        ->label('مكان الميلاد')
                        ->placeholder('-'),

                    TextEntry::make('gender')
                        ->label('الجنس')
                        ->formatStateUsing(fn (?string $state): string => match ($state) {
                            'male' => 'ذكر',
                            'female' => 'أنثى',
                            default => '-',
                        })
                        ->placeholder('-'),

                    TextEntry::make('marital_status')
                        ->label('الحالة الاجتماعية')
                        ->formatStateUsing(fn (?string $state): string => match ($state) {
                            'single' => 'أعزب / عزباء',
                            'married' => 'متزوج / متزوجة',
                            'divorced' => 'مطلق / مطلقة',
                            'widowed' => 'أرمل / أرملة',
                            default => '-',
                        })
                        ->placeholder('-'),

                    TextEntry::make('nationality')
                        ->label('الجنسية')
                        ->placeholder('-'),

                    TextEntry::make('national_id')
                        ->label('الرقم الوطني')
                        ->placeholder('-'),

                    TextEntry::make('card_number')
                        ->label('رقم البطاقة')
                        ->placeholder('-'),

                    TextEntry::make('residence')
                        ->label('مكان الإقامة')
                        ->placeholder('-'),

                    TextEntry::make('permanent_address')
                        ->label('العنوان الدائم')
                        ->columnSpan(2)
                        ->placeholder('-'),

                    TextEntry::make('profession')
                        ->label('المهنة')
                        ->placeholder('-'),

                    TextEntry::make('workplace')
                        ->label('مكان العمل')
                        ->placeholder('-'),

                    IconEntry::make('is_transfer')
                        ->label('منتقل من كلية أخرى')
                        ->boolean(),
                ]),

            Section::make('ثانياً: بيانات التواصل والقسم')
                ->columns(3)
                ->components([
                    TextEntry::make('user.email')
                        ->label('البريد الإلكتروني'),

                    TextEntry::make('user.phone')
                        ->label('رقم النقال')
                        ->placeholder('-'),

                    TextEntry::make('specialization.name')
                        ->label('التخصص')
                        ->placeholder('-'),
                ]),

            Section::make('ثالثاً: نوع الدراسة')
                ->columns(3)
                ->components([
                    TextEntry::make('study_type')
                        ->label('نوع الدراسة')
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
                        ->placeholder('-'),

                    TextEntry::make('delegation_entity')
                        ->label('جهة الإيفاد')
                        ->placeholder('-'),
                ]),

            Section::make('رابعاً: الوثيقة العلمية والمعدل')
                ->columns(2)
                ->components([
                    TextEntry::make('degree_image')
                        ->label('صورة الشهادة')
                        ->formatStateUsing(fn (?string $state): string => $state ? basename($state) : '-')
                        ->url(fn (?string $state): ?string => $state ? asset('storage/'.$state) : null)
                        ->openUrlInNewTab()
                        ->placeholder('لم يُرفق'),

                    TextEntry::make('gpa')
                        ->label('المعدل التراكمي')
                        ->placeholder('-'),
                ]),
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('بيانات الحساب')
                ->columns(2)
                ->visibleOn('create')
                ->components([
                    TextInput::make('user_name')
                        ->label('الاسم الرباعي')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('user_email')
                        ->label('البريد الإلكتروني')
                        ->email()
                        ->required()
                        ->maxLength(255),

                    TextInput::make('user_phone')
                        ->label('رقم النقال')
                        ->tel()
                        ->maxLength(20),

                    TextInput::make('user_password')
                        ->label('كلمة المرور')
                        ->password()
                        ->required()
                        ->minLength(8),
                ]),

            Section::make('البيانات الشخصية')
                ->columns(2)
                ->components([
                    Select::make('specialization_id')
                        ->relationship('specialization', 'name')
                        ->label('التخصص')
                        ->required()
                        ->columnSpan(2),

                    TextInput::make('last_name')
                        ->label('اللقب'),

                    TextInput::make('mother_name')
                        ->label('اسم الأم'),

                    DatePicker::make('birth_date')
                        ->label('تاريخ الميلاد'),

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
                        ->label('العنوان الدائم')
                        ->columnSpan(2),

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

                    Toggle::make('is_transfer')
                        ->label('منتقل من كلية أخرى؟')
                        ->columnSpan(2),
                ]),

            Section::make('الوثيقة العلمية')
                ->components([
                    FileUpload::make('degree_image')
                        ->label('صورة الشهادة (JPG / PNG / PDF)')
                        ->directory('students/degrees')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->maxSize(5120),
                ]),

            Section::make('الحالة الإدارية')
                ->columns(2)
                ->components([
                    Select::make('status')
                        ->label('الحالة')
                        ->options(StudentStatus::class)
                        ->default(StudentStatus::Active)
                        ->required(),

                    TextInput::make('enrollment_no')
                        ->label('رقم القيد')
                        ->disabled()
                        ->placeholder('سيتم توليد رقم القيد تلقائياً')
                        ->dehydrated(),

                    TextInput::make('warnings_count')
                        ->label('عدد الإنذارات')
                        ->numeric()
                        ->default(0),
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

                TextColumn::make('last_name')
                    ->label('اللقب')
                    ->placeholder('-')
                    ->toggleable(),

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
                    ->formatStateUsing(fn (StudentStatus $state): string => $state->label()),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(StudentStatus::class),
                SelectFilter::make('specialization_id')
                    ->relationship('specialization', 'name')
                    ->label('التخصص'),
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
            'edit' => EditStudent::route('/{record}/edit'),
        ];
    }
}
