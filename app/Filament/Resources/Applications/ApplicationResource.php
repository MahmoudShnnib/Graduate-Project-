<?php

namespace App\Filament\Resources\Applications;

use App\Actions\ApproveApplication;
use App\Enums\ApplicationStatus;
use App\Filament\Resources\Applications\Pages\EditApplication;
use App\Filament\Resources\Applications\Pages\ListApplications;
use App\Models\Application;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
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
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->label('المتقدم')
                    ->required()
                    ->searchable()
                    ->preload(),

                Select::make('status')
                    ->label('الحالة')
                    ->options(ApplicationStatus::class)
                    ->default(ApplicationStatus::Pending)
                    ->required(),

                FileUpload::make('bachelor_certificate')
                    ->label('شهادة البكالوريوس')
                    ->directory('applications')
                    ->acceptedFileTypes(['application/pdf', 'image/*']),

                FileUpload::make('remedial_statement')
                    ->label('بيان المعادلة')
                    ->directory('applications')
                    ->acceptedFileTypes(['application/pdf', 'image/*']),

                Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(3),

                Textarea::make('rejection_reason')
                    ->label('سبب الرفض')
                    ->rows(3)
                    ->visible(fn (?Application $record) => $record?->status === ApplicationStatus::Rejected),

                DateTimePicker::make('exam_date')
                    ->label('تاريخ الامتحان')
                    ->visible(fn (?Application $record) => $record?->status === ApplicationStatus::ProvisionallyAccepted),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('المتقدم')
                    ->searchable(),

                TextColumn::make('user.email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),

                TextColumn::make('user.phone')
                    ->label('الهاتف'),

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

                TextColumn::make('exam_date')
                    ->label('تاريخ الامتحان')
                    ->dateTime()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('تاريخ التقديم')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(ApplicationStatus::class),
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
            'edit' => EditApplication::route('/{record}/edit'),
        ];
    }
}
