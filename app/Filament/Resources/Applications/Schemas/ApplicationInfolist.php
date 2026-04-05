<?php

namespace App\Filament\Resources\Applications\Schemas;

use App\Enums\ApplicationStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ApplicationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            // Status banner at the top
            Section::make()
                ->compact()
                ->components([
                    Grid::make(4)
                        ->components([
                            TextEntry::make('user.name')
                                ->label('الاسم الرباعي'),

                            TextEntry::make('last_name')
                                ->label('اللقب')
                                ->placeholder('-'),

                            TextEntry::make('status')
                                ->label('حالة الطلب')
                                ->badge()
                                ->formatStateUsing(fn (ApplicationStatus $state): string => $state->label())
                                ->color(fn (ApplicationStatus $state): string => match ($state) {
                                    ApplicationStatus::Pending => 'warning',
                                    ApplicationStatus::ProvisionallyAccepted => 'info',
                                    ApplicationStatus::Accepted => 'success',
                                    ApplicationStatus::Rejected => 'danger',
                                }),

                            TextEntry::make('created_at')
                                ->label('تاريخ التقديم')
                                ->date(),
                        ]),
                ]),

            // Personal Data
            Section::make('أولاً: البيانات الشخصية')
                ->columns(3)
                ->components([
                    TextEntry::make('mother_name')
                        ->label('اسم الأم')
                        ->placeholder('-'),

                    TextEntry::make('birth_date')
                        ->label('تاريخ الميلاد')
                        ->date()
                        ->placeholder('-'),

                    TextEntry::make('birth_place')
                        ->label('مكان الميلاد')
                        ->placeholder('-'),

                    TextEntry::make('gender')
                        ->label('الجنس')
                        ->formatStateUsing(fn (?string $state): string => match ($state) {
                            'male' => 'ذكر',
                            'female' => 'أنثى',
                            default => '-',
                        })
                        ->placeholder('-'),

                    TextEntry::make('marital_status')
                        ->label('الحالة الاجتماعية')
                        ->formatStateUsing(fn (?string $state): string => match ($state) {
                            'single' => 'أعزب / عزباء',
                            'married' => 'متزوج / متزوجة',
                            'divorced' => 'مطلق / مطلقة',
                            'widowed' => 'أرمل / أرملة',
                            default => '-',
                        })
                        ->placeholder('-'),

                    TextEntry::make('nationality')
                        ->label('الجنسية')
                        ->placeholder('-'),

                    TextEntry::make('national_id')
                        ->label('الرقم الوطني')
                        ->placeholder('-'),

                    TextEntry::make('card_number')
                        ->label('رقم البطاقة')
                        ->placeholder('-'),

                    TextEntry::make('residence')
                        ->label('مكان الإقامة')
                        ->placeholder('-'),

                    TextEntry::make('permanent_address')
                        ->label('العنوان الدائم')
                        ->columnSpan(2)
                        ->placeholder('-'),

                    TextEntry::make('profession')
                        ->label('المهنة')
                        ->placeholder('-'),

                    TextEntry::make('workplace')
                        ->label('مكان العمل')
                        ->placeholder('-'),
                ]),

            // Contact & Academic
            Section::make('ثانياً: بيانات التواصل والقسم')
                ->columns(3)
                ->components([
                    TextEntry::make('user.email')
                        ->label('البريد الإلكتروني'),

                    TextEntry::make('user.phone')
                        ->label('رقم النقال')
                        ->placeholder('-'),

                    TextEntry::make('user.specialization.name')
                        ->label('التخصص')
                        ->placeholder('-'),
                ]),

            // Study Type
            Section::make('ثالثاً: نوع الدراسة')
                ->columns(3)
                ->components([
                    TextEntry::make('study_type')
                        ->label('نوع الدراسة')
                        ->formatStateUsing(fn (?string $state): string => match ($state) {
                            'self_funded' => 'على حسابي الخاص',
                            'internally_delegated' => 'موفد بقرار إيفاد داخلي',
                            'faculty_assistant' => 'معيد بالكلية',
                            'public_delegation' => 'موفد من جهة عامة',
                            default => '-',
                        })
                        ->placeholder('-'),

                    TextEntry::make('delegation_number')
                        ->label('رقم قرار الإيفاد')
                        ->placeholder('-'),

                    TextEntry::make('delegation_entity')
                        ->label('جهة الإيفاد')
                        ->placeholder('-'),

                    \Filament\Infolists\Components\IconEntry::make('is_transfer')
                        ->label('منتقل من كلية أخرى')
                        ->boolean(),
                ]),

            // Documents & Admin Notes
            Section::make('رابعاً: المستندات والملاحظات الإدارية')
                ->columns(2)
                ->components([
                    TextEntry::make('bachelor_certificate')
                        ->label('شهادة البكالوريوس')
                        ->formatStateUsing(fn (?string $state): string => $state ? basename($state) : '-')
                        ->url(fn (?string $state): ?string => $state ? asset('storage/'.$state) : null)
                        ->openUrlInNewTab()
                        ->placeholder('لم يُرفق'),

                    TextEntry::make('remedial_statement')
                        ->label('كشف المواد التكميلية')
                        ->formatStateUsing(fn (?string $state): string => $state ? basename($state) : '-')
                        ->url(fn (?string $state): ?string => $state ? asset('storage/'.$state) : null)
                        ->openUrlInNewTab()
                        ->placeholder('لم يُرفق'),

                    TextEntry::make('notes')
                        ->label('ملاحظات')
                        ->columnSpan(2)
                        ->placeholder('-'),

                    TextEntry::make('rejection_reason')
                        ->label('سبب الرفض')
                        ->columnSpan(2)
                        ->placeholder('-')
                        ->visible(fn ($record): bool => $record?->status === ApplicationStatus::Rejected),

                    TextEntry::make('exam_date')
                        ->label('تاريخ الامتحان')
                        ->dateTime()
                        ->placeholder('-')
                        ->visible(fn ($record): bool => $record?->status === ApplicationStatus::ProvisionallyAccepted),
                ]),
        ]);
    }
}
