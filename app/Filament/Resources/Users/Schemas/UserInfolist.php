<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                // Right column (RTL): Personal data
                Grid::make(1)
                    ->columnSpan(1)
                    ->components([
                        Section::make('البيانات الشخصية')
                            ->extraAttributes(['class' => 'fsv-card fsv-card-personal'])
                            ->components([
                                TextEntry::make('name')
                                    ->label('الاسم بالعربية')
                                    ->icon(Heroicon::User)
                                    ->inlineLabel()
                                    ->placeholder('-'),

                                TextEntry::make('role')
                                    ->label('الدور')
                                    ->icon(Heroicon::Briefcase)
                                    ->inlineLabel()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'super_admin' => 'مشرف عام',
                                        'secretary' => 'سكرتير',
                                        'faculty' => 'عضو هيئة تدريس',
                                        default => $state ?? '-',
                                    })
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'super_admin' => 'danger',
                                        'secretary' => 'warning',
                                        'faculty' => 'success',
                                        default => 'gray',
                                    })
                                    ->placeholder('-'),

                                IconEntry::make('is_active')
                                    ->label('الحالة')
                                    ->inlineLabel()
                                    ->boolean(),
                            ]),

                        Section::make('البيانات الأكاديمية')
                            ->extraAttributes(['class' => 'fsv-card fsv-card-academic'])
                            ->visible(fn ($record): bool => $record?->role === 'faculty'
                                || filled($record?->academic_rank)
                                || filled($record?->qualification)
                                || filled($record?->institution))
                            ->components([
                                TextEntry::make('academic_rank')
                                    ->label('الدرجة العلمية')
                                    ->icon(Heroicon::Star)
                                    ->inlineLabel()
                                    ->placeholder('-'),

                                TextEntry::make('qualification')
                                    ->label('المؤهل العلمي')
                                    ->icon(Heroicon::Sparkles)
                                    ->inlineLabel()
                                    ->placeholder('-'),

                                TextEntry::make('institution')
                                    ->label('الجامعة / الكلية / المعهد')
                                    ->icon(Heroicon::BuildingLibrary)
                                    ->inlineLabel()
                                    ->placeholder('-'),
                            ]),
                    ]),

                // Left column (RTL): Contact + Additional
                Grid::make(1)
                    ->columnSpan(1)
                    ->components([
                        Section::make('بيانات التواصل')
                            ->extraAttributes(['class' => 'fsv-card fsv-card-contact'])
                            ->components([
                                TextEntry::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->icon(Heroicon::Envelope)
                                    ->inlineLabel()
                                    ->weight('semibold')
                                    ->placeholder('-'),

                                TextEntry::make('phone')
                                    ->label('الهاتف')
                                    ->icon(Heroicon::Phone)
                                    ->inlineLabel()
                                    ->weight('semibold')
                                    ->placeholder('-'),
                            ]),

                        Section::make('بيانات إضافية')
                            ->extraAttributes(['class' => 'fsv-card fsv-card-extra'])
                            ->components([
                                TextEntry::make('created_at')
                                    ->label('تاريخ التسجيل')
                                    ->icon(Heroicon::CalendarDays)
                                    ->inlineLabel()
                                    ->dateTime()
                                    ->placeholder('-'),
                            ]),
                    ]),
            ]);
    }
}
