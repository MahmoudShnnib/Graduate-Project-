<?php

namespace App\Filament\Resources\Semesters\RelationManagers;

use App\Enums\ApplicationStatus;
use App\Filament\Resources\Applications\ApplicationResource;
use App\Models\Application;
use App\Models\Semester;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DecidedApplicationsRelationManager extends RelationManager
{
    protected static string $relationship = 'applications';

    protected static ?string $title = 'طلبات التسجيل المُقيّمة';

    protected static ?string $modelLabel = 'طلب';

    protected static ?string $pluralModelLabel = 'طلبات';

    /**
     * Only show this relation manager on archived semesters. Active semesters
     * have their own dedicated /admin/applications page — no need to duplicate.
     */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Semester && $ownerRecord->isArchived();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [
                ApplicationStatus::Accepted,
                ApplicationStatus::Rejected,
            ]))
            ->recordUrl(fn (Application $record): string => ApplicationResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('tracking_code')
                    ->label('رمز التتبع')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable(),

                TextColumn::make('specialization.name')
                    ->label('التخصص')
                    ->badge()
                    ->color('info'),

                TextColumn::make('status')
                    ->label('القرار')
                    ->badge()
                    ->formatStateUsing(fn (ApplicationStatus $state) => $state->label())
                    ->color(fn (ApplicationStatus $state): string => match ($state) {
                        ApplicationStatus::Accepted => 'success',
                        ApplicationStatus::Rejected => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('updated_at')
                    ->label('تاريخ القرار')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('القرار')
                    ->options([
                        ApplicationStatus::Accepted->value => ApplicationStatus::Accepted->label(),
                        ApplicationStatus::Rejected->value => ApplicationStatus::Rejected->label(),
                    ]),
            ])
            ->headerActions([])
            ->recordActions([
                Action::make('view')
                    ->label('عرض')
                    ->icon(\Filament\Support\Icons\Heroicon::Eye)
                    ->color('info')
                    ->url(fn (Application $record): string => ApplicationResource::getUrl('view', ['record' => $record])),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
