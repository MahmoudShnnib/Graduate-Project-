<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'سجل التدقيق';

    protected static ?string $modelLabel = 'سجل';

    protected static ?string $pluralModelLabel = 'سجل التدقيق';

    protected static string|\UnitEnum|null $navigationGroup = 'النظام';

    protected static ?int $navigationSort = 99;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('المستخدم')
                    ->placeholder('النظام'),

                TextColumn::make('action')
                    ->label('الإجراء')
                    ->searchable()
                    ->badge(),

                TextColumn::make('model_type')
                    ->label('النموذج')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->searchable(),

                TextColumn::make('model_id')
                    ->label('المعرف'),

                TextColumn::make('old_values')
                    ->label('القيم القديمة')
                    ->formatStateUsing(fn (?array $state): string => $state ? json_encode($state, JSON_UNESCAPED_UNICODE) : '-')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('new_values')
                    ->label('القيم الجديدة')
                    ->formatStateUsing(fn (?array $state): string => $state ? json_encode($state, JSON_UNESCAPED_UNICODE) : '-')
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('ip_address')
                    ->label('عنوان IP')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('الإجراء')
                    ->options(fn () => AuditLog::distinct()->pluck('action', 'action')->toArray()),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
        ];
    }
}
