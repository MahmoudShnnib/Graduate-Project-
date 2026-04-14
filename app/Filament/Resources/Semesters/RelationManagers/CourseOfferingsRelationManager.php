<?php

namespace App\Filament\Resources\Semesters\RelationManagers;

use App\Filament\Resources\CourseOfferings\CourseOfferingResource;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CourseOfferingsRelationManager extends RelationManager
{
    protected static string $relationship = 'courseOfferings';

    protected static ?string $title = 'المواد المفتوحة في هذا الفصل';

    protected static ?string $modelLabel = 'مادة';

    protected static ?string $pluralModelLabel = 'مواد';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('course_id')
                ->label('المادة')
                ->options(fn () => Course::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->required(),

            Select::make('faculty_id')
                ->label('عضو هيئة التدريس')
                ->options(fn () => User::query()->where('role', 'faculty')->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->placeholder('اختر عضو هيئة التدريس'),

            Toggle::make('is_open')
                ->label('مفتوح للتسجيل')
                ->default(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (CourseOffering $record): string => CourseOfferingResource::getUrl('enroll', ['record' => $record]))
            ->columns([
                TextColumn::make('course.name')
                    ->label('اسم المادة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('course.code')
                    ->label('الرمز')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('course.credits')
                    ->label('الساعات')
                    ->badge()
                    ->color('info'),

                TextColumn::make('faculty.name')
                    ->label('عضو هيئة التدريس')
                    ->placeholder('غير مسند')
                    ->badge()
                    ->color('warning'),

                TextColumn::make('enrollments_count')
                    ->label('عدد الطلاب')
                    ->counts('enrollments')
                    ->badge()
                    ->color('success'),

                IconColumn::make('is_open')
                    ->label('مفتوح للتسجيل')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('فتح مادة')
                    ->icon(Heroicon::PlusCircle)
                    ->modalHeading('فتح مادة في هذا الفصل')
                    ->successNotificationTitle('تم فتح المادة بنجاح'),
            ])
            ->recordActions([
                Action::make('toggle_open')
                    ->label(fn (CourseOffering $record): string => $record->is_open ? 'إغلاق' : 'فتح')
                    ->icon(fn (CourseOffering $record) => $record->is_open ? Heroicon::LockClosed : Heroicon::LockOpen)
                    ->color(fn (CourseOffering $record): string => $record->is_open ? 'warning' : 'success')
                    ->action(function (CourseOffering $record): void {
                        $record->update(['is_open' => ! $record->is_open]);

                        Notification::make()
                            ->title($record->is_open ? 'تم فتح المادة للتسجيل' : 'تم إغلاق المادة')
                            ->success()
                            ->send();
                    }),

                EditAction::make()
                    ->label('تعديل')
                    ->modalHeading('تعديل بيانات المادة'),

                DeleteAction::make()
                    ->label('حذف'),
            ]);
    }
}
