<?php

namespace App\Filament\Resources\Applications;

use App\Actions\ApproveApplication;
use App\Enums\ApplicationStatus;
use App\Filament\Resources\Applications\Pages\EditApplication;
use App\Filament\Resources\Applications\Pages\ListApplications;
use App\Filament\Resources\Applications\Pages\ViewApplication;
use App\Filament\Resources\Applications\Schemas\ApplicationInfolist;
use App\Models\Application;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static ?string $navigationLabel = 'التسجيل والقبول';

    protected static ?string $modelLabel = 'طلب';

    protected static ?string $pluralModelLabel = 'طلبات التسجيل';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

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
                    Select::make('user_id')
                        ->relationship('user', 'name')
                        ->label('المتقدم')
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
                        ->visible(fn ($get): bool => $get('status') === ApplicationStatus::ProvisionallyAccepted->value),

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
            ->columns([
                TextColumn::make('user.name')
                    ->label('الاسم الرباعي')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('last_name')
                    ->label('اللقب')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('national_id')
                    ->label('الرقم الوطني')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('user.phone')
                    ->label('النقال')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('user.specialization.name')
                    ->label('التخصص')
                    ->placeholder('-')
                    ->toggleable(),

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
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (ApplicationStatus $state): string => match ($state) {
                        ApplicationStatus::Pending => 'warning',
                        ApplicationStatus::ProvisionallyAccepted => 'info',
                        ApplicationStatus::Accepted => 'success',
                        ApplicationStatus::Rejected => 'danger',
                    })
                    ->formatStateUsing(fn (ApplicationStatus $state): string => $state->label()),

                TextColumn::make('created_at')
                    ->label('تاريخ التقديم')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
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
                Action::make('approve')
                    ->label('قبول')
                    ->icon(Heroicon::Check)
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Application $application, ApproveApplication $approve) {
                        try {
                            $approve->handle($application);
                            Notification::make()->title('تم القبول بنجاح')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('خطأ')->body($e->getMessage())->danger()->send();
                        }
                    })
                    ->visible(fn (Application $record) => $record->status === ApplicationStatus::Pending),
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
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
}
