<?php

namespace App\Filament\Resources\Graduates;

use App\Enums\DiscussionStatus;
use App\Enums\StudentStatus;
use App\Filament\Resources\Graduates\Pages\ListGraduates;
use App\Filament\Resources\Graduates\Pages\ViewGraduate;
use App\Models\DiscussionRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GraduateResource extends Resource
{
    protected static ?string $model = DiscussionRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $navigationLabel = 'الخريجون';

    protected static ?string $modelLabel = 'خريج';

    protected static ?string $pluralModelLabel = 'الخريجون';

    protected static string|\UnitEnum|null $navigationGroup = 'طلبات المناقشة';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'graduates';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $latestIds = DiscussionRequest::query()
            ->selectRaw('MAX(id) as id')
            ->groupBy('student_id')
            ->pluck('id');

        return parent::getEloquentQuery()
            ->whereIn('id', $latestIds)
            ->where('status', DiscussionStatus::Completed)
            ->with([
                'student.user',
                'student.specialization',
                'thesisRegistration',
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (DiscussionRequest $record): string => static::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('student.enrollment_no')
                    ->label('رقم القيد')
                    ->searchable(),

                TextColumn::make('student.user.name')
                    ->label('الطالب')
                    ->searchable(),

                TextColumn::make('student.specialization.name')
                    ->label('التخصص'),

                TextColumn::make('thesisRegistration.title')
                    ->label('عنوان الرسالة')
                    ->limit(40),

                TextColumn::make('student.gpa')
                    ->label('المعدل')
                    ->numeric(2),

                TextColumn::make('student.status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (StudentStatus $state): string => $state->getColor())
                    ->formatStateUsing(fn (StudentStatus $state): string => $state->getLabel()),

                TextColumn::make('completed_at')
                    ->label('تاريخ التجليد والتسليم')
                    ->date('Y/m/d')
                    ->sortable(),
            ])
            ->defaultSort(fn (Builder $query): Builder => $query
                ->orderByRaw(
                    '(select case when students.status = ? then 1 else 0 end '
                    .'from students where students.id = discussion_requests.student_id) asc',
                    [StudentStatus::Graduated->value]
                )
                ->orderByDesc('completed_at'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGraduates::route('/'),
            'view' => ViewGraduate::route('/{record}'),
        ];
    }
}
