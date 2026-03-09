<?php

namespace App\Filament\Resources\Applications;

use App\Filament\Resources\Applications\Pages\CreateApplication;
use App\Filament\Resources\Applications\Pages\EditApplication;
use App\Filament\Resources\Applications\Pages\ListApplications;
use App\Models\Application;
use App\Models\Student;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('application_number')
                    ->label('رقم الطلب')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                TextInput::make('full_name')
                    ->label('اسم المتقدم')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('البريد الإلكتروني')
                    ->email()
                    ->maxLength(255),

                TextInput::make('phone')
                    ->label('رقم الهاتف')
                    ->maxLength(255),

                Select::make('specialization_id')
                    ->relationship('specialization', 'name')
                    ->label('التخصص')
                    ->required(),

                DatePicker::make('submitted_at')
                    ->label('تاريخ التقديم')
                    ->native(false),

                Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'under_review' => 'قيد المراجعة',
                        'accepted' => 'مقبول',
                        'rejected' => 'مرفوض',
                    ])
                    ->default('pending')
                    ->required()
                    ->native(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('application_number')
                    ->label('رقم الطلب')
                    ->searchable(),

                TextColumn::make('full_name')
                    ->label('المتقدم')
                    ->searchable(),

                TextColumn::make('specialization.name')
                    ->label('التخصص'),

                TextColumn::make('submitted_at')
                    ->label('تاريخ التقديم')
                    ->date(),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'under_review' => 'info',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending' => 'قيد الانتظار',
                        'under_review' => 'قيد المراجعة',
                        'accepted' => 'مقبول',
                        'rejected' => 'مرفوض',
                        default => $state,
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'under_review' => 'قيد المراجعة',
                        'accepted' => 'مقبول',
                        'rejected' => 'مرفوض',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->after(function ($record) {

                        if ($record->status === 'accepted') {

                            Student::create([
                                'student_number' => Student::generateStudentNumber(
                                    $record->specialization_id,
                                    1 // نوع الفصل
                                ),

                                'specialization_id' => $record->specialization_id,
                                'admission_year' => now()->year,
                                'semester_type' => 1,
                            ]);
                        }

                    }),
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
            'edit' => EditApplication::route('/{record}/edit'),
        ];
    }
}
