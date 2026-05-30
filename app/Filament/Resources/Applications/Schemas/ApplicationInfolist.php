<?php

namespace App\Filament\Resources\Applications\Schemas;

use App\Enums\ApplicationStatus;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ApplicationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                // Right column (RTL): Personal data — taller column
                Grid::make(1)
                    ->columnSpan(1)
                    ->components([
                        Section::make('البيانات الشخصية')
                            ->extraAttributes(['class' => 'fsv-card fsv-card-personal'])
                            ->components([
                                TextEntry::make('name')
                                    ->label('الاسم الرباعي')
                                    ->icon(Heroicon::User)
                                    ->inlineLabel()
                                    ->placeholder('—'),

                                TextEntry::make('name_en')
                                    ->label('الاسم بالإنجليزية')
                                    ->icon(Heroicon::User)
                                    ->inlineLabel()
                                    ->placeholder('—'),

                                TextEntry::make('last_name')
                                    ->label('اللقب')
                                    ->icon(Heroicon::UserCircle)
                                    ->inlineLabel()
                                    ->placeholder('—'),

                                TextEntry::make('mother_name')
                                    ->label('اسم الأم')
                                    ->icon(Heroicon::User)
                                    ->inlineLabel()
                                    ->placeholder('—'),

                                TextEntry::make('birth_date')
                                    ->label('تاريخ الميلاد')
                                    ->icon(Heroicon::CalendarDays)
                                    ->inlineLabel()
                                    ->date()
                                    ->placeholder('—'),

                                TextEntry::make('birth_place')
                                    ->label('مكان الميلاد')
                                    ->icon(Heroicon::MapPin)
                                    ->inlineLabel()
                                    ->placeholder('—'),

                                TextEntry::make('gender')
                                    ->label('الجنس')
                                    ->icon(Heroicon::Identification)
                                    ->inlineLabel()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'male' => 'ذكر',
                                        'female' => 'أنثى',
                                        default => '—',
                                    }),

                                TextEntry::make('marital_status')
                                    ->label('الحالة الاجتماعية')
                                    ->icon(Heroicon::Heart)
                                    ->inlineLabel()
                                    ->formatStateUsing(function (?string $state, $record): string {
                                        $female = ($record->gender ?? null) === 'female';

                                        return match ($state) {
                                            'single' => $female ? 'عزباء' : 'أعزب',
                                            'married' => $female ? 'متزوجة' : 'متزوج',
                                            'divorced' => $female ? 'مطلقة' : 'مطلق',
                                            'widowed' => $female ? 'أرملة' : 'أرمل',
                                            default => '—',
                                        };
                                    }),

                                TextEntry::make('nationality')
                                    ->label('الجنسية')
                                    ->icon(Heroicon::Flag)
                                    ->inlineLabel()
                                    ->placeholder('—'),

                                TextEntry::make('national_id')
                                    ->label('الرقم الوطني')
                                    ->icon(Heroicon::Hashtag)
                                    ->inlineLabel()
                                    ->placeholder('—'),

                                TextEntry::make('card_number')
                                    ->label('رقم البطاقة')
                                    ->icon(Heroicon::Identification)
                                    ->inlineLabel()
                                    ->placeholder('—'),
                            ]),
                    ]),

                // Left column (RTL): Contact + Study type + Address/work
                Grid::make(1)
                    ->columnSpan(1)
                    ->components([
                        Section::make('بيانات التواصل والموقع')
                            ->extraAttributes(['class' => 'fsv-card fsv-card-contact'])
                            ->components([
                                TextEntry::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->icon(Heroicon::Envelope)
                                    ->inlineLabel()
                                    ->weight('semibold')
                                    ->placeholder('—'),

                                TextEntry::make('phone')
                                    ->label('رقم النقال')
                                    ->icon(Heroicon::Phone)
                                    ->inlineLabel()
                                    ->weight('semibold')
                                    ->placeholder('—'),

                                TextEntry::make('residence')
                                    ->label('مكان الإقامة')
                                    ->icon(Heroicon::MapPin)
                                    ->inlineLabel()
                                    ->placeholder('—'),

                                TextEntry::make('permanent_address')
                                    ->label('العنوان الدائم')
                                    ->icon(Heroicon::Home)
                                    ->inlineLabel()
                                    ->placeholder('—'),

                                TextEntry::make('profession')
                                    ->label('المهنة')
                                    ->icon(Heroicon::Briefcase)
                                    ->inlineLabel()
                                    ->placeholder('—'),

                                TextEntry::make('workplace')
                                    ->label('مكان العمل')
                                    ->icon(Heroicon::BuildingOffice)
                                    ->inlineLabel()
                                    ->placeholder('—'),
                            ]),

                        Section::make('بيانات الدراسة')
                            ->extraAttributes(['class' => 'fsv-card fsv-card-study'])
                            ->components([
                                TextEntry::make('specialization.name')
                                    ->label('التخصص')
                                    ->icon(Heroicon::AcademicCap)
                                    ->inlineLabel()
                                    ->placeholder('—'),

                                TextEntry::make('study_type')
                                    ->label('نوع الدراسة')
                                    ->icon(Heroicon::BookOpen)
                                    ->inlineLabel()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'self_funded' => 'على حسابي الخاص',
                                        'internally_delegated' => 'موفد بقرار إيفاد داخلي',
                                        'faculty_assistant' => 'معيد بالكلية',
                                        'public_delegation' => 'موفد من جهة عامة',
                                        default => '—',
                                    }),

                                TextEntry::make('delegation_number')
                                    ->label('رقم قرار الإيفاد')
                                    ->icon(Heroicon::DocumentText)
                                    ->inlineLabel()
                                    ->placeholder('—')
                                    ->visible(fn ($record): bool => in_array(
                                        $record?->study_type,
                                        ['internally_delegated', 'faculty_assistant', 'public_delegation']
                                    )),

                                TextEntry::make('delegation_entity')
                                    ->label('جهة الإيفاد')
                                    ->icon(Heroicon::BuildingLibrary)
                                    ->inlineLabel()
                                    ->placeholder('—')
                                    ->visible(fn ($record): bool => in_array(
                                        $record?->study_type,
                                        ['faculty_assistant', 'public_delegation']
                                    )),

                                TextEntry::make('is_transfer')
                                    ->hiddenLabel()
                                    ->state('ملاحظة: هذا الطالب منتقل من كلية أخرى')
                                    ->icon(Heroicon::InformationCircle)
                                    ->color('danger')
                                    ->extraAttributes(['class' => 'fsv-transfer-callout'])
                                    ->visible(fn ($record): bool => (bool) $record?->is_transfer),
                            ]),
                    ]),

                // Documents — full width
                Section::make('المستندات')
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fsv-card fsv-card-docs'])
                    ->columns(2)
                    ->components([
                        TextEntry::make('bachelor_certificate')
                            ->label('شهادة البكالوريوس')
                            ->icon(Heroicon::DocumentText)
                            ->formatStateUsing(fn (?string $state): string => $state ? basename($state) : '—')
                            ->url(fn (?string $state): ?string => $state ? asset('storage/'.$state) : null)
                            ->openUrlInNewTab()
                            ->placeholder('لم يُرفق'),

                        TextEntry::make('remedial_statement')
                            ->label('كشف المواد التكميلية')
                            ->icon(Heroicon::DocumentText)
                            ->formatStateUsing(fn (?string $state): string => $state ? basename($state) : '—')
                            ->url(fn (?string $state): ?string => $state ? asset('storage/'.$state) : null)
                            ->openUrlInNewTab()
                            ->placeholder('لم يُرفق'),
                    ]),

                // Admin notes + exam — full width
                Section::make('القرار الإداري والامتحان')
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fsv-card fsv-card-admin'])
                    ->columns(2)
                    ->components([
                        TextEntry::make('notes')
                            ->label('ملاحظات')
                            ->columnSpanFull()
                            ->placeholder('لا توجد ملاحظات'),

                        TextEntry::make('rejection_reason')
                            ->label('سبب الرفض')
                            ->columnSpanFull()
                            ->placeholder('—')
                            ->visible(fn ($record): bool => $record?->status === ApplicationStatus::Rejected),

                        TextEntry::make('exam_date')
                            ->label('تاريخ الامتحان')
                            ->icon(Heroicon::CalendarDays)
                            ->inlineLabel()
                            ->dateTime()
                            ->placeholder('—')
                            ->visible(fn ($record): bool => $record?->exam_date !== null),

                        TextEntry::make('exam_score')
                            ->label('درجة الامتحان')
                            ->icon(Heroicon::ChartBar)
                            ->inlineLabel()
                            ->placeholder('—')
                            ->visible(fn ($record): bool => $record?->exam_passed !== null),

                        IconEntry::make('exam_passed')
                            ->label('نتيجة الامتحان')
                            ->inlineLabel()
                            ->boolean()
                            ->visible(fn ($record): bool => $record?->exam_passed !== null),

                        TextEntry::make('exam_result_date')
                            ->label('تاريخ إعلان النتيجة')
                            ->icon(Heroicon::Clock)
                            ->inlineLabel()
                            ->dateTime()
                            ->placeholder('—')
                            ->visible(fn ($record): bool => $record?->exam_result_date !== null),
                    ]),
            ]);
    }
}
