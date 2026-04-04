<?php

namespace App\Filament\Resources\StudentEnrollments;

use App\Enums\EnrollmentStatus;
use App\Filament\Resources\StudentEnrollments\Pages\ListStudentEnrollments;
use App\Models\StudentEnrollment;
use BackedEnum;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StudentEnrollmentResource extends Resource
{
    protected static ?string $model = StudentEnrollment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'التسجيلات';

    protected static ?string $modelLabel = 'تسجيل';

    protected static ?string $pluralModelLabel = 'التسجيلات';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة الطلاب';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.user.name')
                    ->label('الطالب')
                    ->searchable(),

                TextColumn::make('student.enrollment_no')
                    ->label('رقم القيد'),

                TextColumn::make('courseOffering.course.name')
                    ->label('المقرر'),

                TextColumn::make('courseOffering.semester.name')
                    ->label('الفصل'),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (EnrollmentStatus $state): string => $state->label())
                    ->color(fn (EnrollmentStatus $state): string => match ($state) {
                        EnrollmentStatus::Enrolled => 'success',
                        EnrollmentStatus::Dropped => 'warning',
                        EnrollmentStatus::Withdrawn => 'danger',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(EnrollmentStatus::class),

                SelectFilter::make('semester')
                    ->label('الفصل')
                    ->relationship('courseOffering.semester', 'name'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStudentEnrollments::route('/'),
        ];
    }
}
