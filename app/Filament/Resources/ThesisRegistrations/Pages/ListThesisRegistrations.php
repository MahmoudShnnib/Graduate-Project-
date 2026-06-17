<?php

namespace App\Filament\Resources\ThesisRegistrations\Pages;

use App\Enums\ThesisStatus;
use App\Filament\Resources\ThesisRegistrations\ThesisRegistrationResource;
use App\Models\Supervisor;
use App\Models\ThesisRegistration;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListThesisRegistrations extends ListRecords
{
    protected static string $resource = ThesisRegistrationResource::class;

    public function getTabs(): array
    {
        $pendingStates = [ThesisStatus::Pending, ThesisStatus::Printed];

        return [
            'all' => Tab::make('الكل')
                ->badge(fn (): int => ThesisRegistration::query()->count()),

            'pending' => Tab::make('قيد المراجعة')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', $pendingStates))
                ->badgeColor('warning')
                ->badge(fn (): int => ThesisRegistration::query()
                    ->whereIn('status', $pendingStates)->count()),

            'approved' => Tab::make('مقبول')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ThesisStatus::Approved))
                ->badgeColor('success')
                ->badge(fn (): int => ThesisRegistration::query()
                    ->where('status', ThesisStatus::Approved)->count()),

            'rejected' => Tab::make('مرفوض')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ThesisStatus::Rejected))
                ->badgeColor('danger')
                ->badge(fn (): int => ThesisRegistration::query()
                    ->where('status', ThesisStatus::Rejected)->count()),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manage_supervisors')
                ->label('مشرفون من خارج الجامعة')
                ->icon(Heroicon::OutlinedUserGroup)
                ->color('warning')
                ->slideOver()
                ->modalHeading('إدارة المشرفين الخارجيين')
                ->modalDescription('أعضاء هيئة التدريس بالجامعة متاحون تلقائياً كمشرفين. أضف هنا المشرفين من خارج الجامعة فقط.')
                ->modalCancelActionLabel('إلغاء')
                ->modalWidth('3xl')
                ->form([
                    Repeater::make('supervisors')
                        ->label('')
                        ->schema([
                            Hidden::make('id'),
                            TextInput::make('name')
                                ->label('اسم المشرف')
                                ->required()
                                ->columnSpan(2),
                            TextInput::make('national_id')
                                ->label('الرقم الوطني')
                                ->required()
                                ->unique('supervisors', 'national_id', ignoreRecord: false, modifyRuleUsing: fn ($rule, $get) => $rule->ignore($get('id'))),
                            DatePicker::make('birth_date')
                                ->label('تاريخ الميلاد')
                                ->required()
                                ->native(false)
                                ->displayFormat('Y/m/d'),
                            TextInput::make('birth_place')
                                ->label('مكان الميلاد')
                                ->required(),
                            Select::make('nationality')
                                ->label('الجنسية')
                                ->options(collect(config('nationalities.list'))->mapWithKeys(fn (array $c): array => [$c['name'] => $c['flag'].' '.$c['name']]))
                                ->default(config('nationalities.default'))
                                ->searchable()
                                ->required(),
                            Select::make('gender')
                                ->label('الجنس')
                                ->options([
                                    'male' => 'ذكر',
                                    'female' => 'أنثى',
                                ])
                                ->required()
                                ->searchable()
                                ->preload()
                                ->columnSpan(2),
                            TextInput::make('academic_rank')
                                ->label('الدرجة الأكاديمية')
                                ->required()
                                ->placeholder('مثال: أستاذ، أستاذ مشارك'),
                            TextInput::make('qualification')
                                ->label('المؤهل العلمي')
                                ->required()
                                ->placeholder('مثال: دكتوراه'),
                            TextInput::make('institution')
                                ->label('جهة العمل / المؤسسة التعليمية')
                                ->required()
                                ->columnSpan(2),
                            TextInput::make('email')
                                ->label('البريد الالكتروني')
                                ->email(),
                            TextInput::make('phone')
                                ->label('رقم الهاتف'),
                        ])
                        ->columns(2)
                        ->addActionLabel('إضافة مشرف')
                        ->defaultItems(0)
                        ->reorderable(false)
                        ->collapsible()
                        ->collapsed()
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                ])
                ->fillForm(fn (): array => [
                    'supervisors' => Supervisor::orderBy('name')
                        ->get(['id', 'name', 'national_id', 'birth_date', 'birth_place', 'nationality', 'gender', 'academic_rank', 'qualification', 'institution', 'email', 'phone'])
                        ->map(fn (Supervisor $s) => [
                            'id' => $s->id,
                            'name' => $s->name,
                            'national_id' => $s->national_id,
                            'birth_date' => optional($s->birth_date)->toDateString(),
                            'birth_place' => $s->birth_place,
                            'nationality' => $s->nationality,
                            'gender' => $s->gender,
                            'academic_rank' => $s->academic_rank,
                            'qualification' => $s->qualification,
                            'institution' => $s->institution,
                            'email' => $s->email,
                            'phone' => $s->phone,
                        ])
                        ->values()
                        ->all(),
                ])
                ->action(function (array $data) {
                    $submittedIds = collect($data['supervisors'])
                        ->pluck('id')
                        ->filter()
                        ->all();

                    Supervisor::whereNotIn('id', $submittedIds)->delete();

                    foreach ($data['supervisors'] as $row) {
                        $payload = [
                            'name' => $row['name'],
                            'national_id' => $row['national_id'],
                            'birth_date' => $row['birth_date'],
                            'birth_place' => $row['birth_place'],
                            'nationality' => $row['nationality'],
                            'gender' => $row['gender'],
                            'academic_rank' => $row['academic_rank'],
                            'qualification' => $row['qualification'],
                            'institution' => $row['institution'],
                            'email' => $row['email'] ?? null,
                            'phone' => $row['phone'] ?? null,
                        ];

                        if (! empty($row['id'])) {
                            Supervisor::where('id', $row['id'])->update($payload);
                        } else {
                            Supervisor::create($payload);
                        }
                    }

                    Notification::make()
                        ->title('تم حفظ المشرفين الخارجيين')
                        ->success()
                        ->send();
                })
                ->modalSubmitActionLabel('حفظ التغييرات'),
        ];
    }
}
