<?php

namespace App\Filament\Resources\DiscussionRequests;

use App\Enums\DiscussionStatus;
use App\Filament\Resources\DiscussionRequests\Pages\ListDiscussionRequests;
use App\Filament\Resources\DiscussionRequests\Pages\ViewDiscussionRequest;
use App\Models\DiscussionRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DiscussionRequestResource extends Resource
{
    protected static ?string $model = DiscussionRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'طلبات المناقشة';

    protected static ?string $modelLabel = 'طلب مناقشة';

    protected static ?string $pluralModelLabel = 'طلبات المناقشة';

    protected static string|\UnitEnum|null $navigationGroup = 'طلبات المناقشة';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) DiscussionRequest::where('status', DiscussionStatus::Pending)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

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
            ->where('status', '!=', DiscussionStatus::Completed);
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

                TextColumn::make('thesisRegistration.title')
                    ->label('عنوان الرسالة')
                    ->limit(40),

                TextColumn::make('discussion_date')
                    ->label('موعد المناقشة')
                    ->date('Y/m/d')
                    ->placeholder('—'),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (DiscussionStatus $state): string => $state->getColor())
                    ->formatStateUsing(fn (DiscussionStatus $state): string => $state->getLabel()),

                TextColumn::make('created_at')
                    ->label('تاريخ الطلب')
                    ->date('Y/m/d')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(DiscussionStatus::class),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiscussionRequests::route('/'),
            'view' => ViewDiscussionRequest::route('/{record}'),
        ];
    }
}
