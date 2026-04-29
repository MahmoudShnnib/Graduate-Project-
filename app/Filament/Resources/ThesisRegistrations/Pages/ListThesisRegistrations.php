<?php

namespace App\Filament\Resources\ThesisRegistrations\Pages;

use App\Filament\Resources\ThesisRegistrations\ThesisRegistrationResource;
use App\Models\Supervisor;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListThesisRegistrations extends ListRecords
{
    protected static string $resource = ThesisRegistrationResource::class;

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
                            TextInput::make('academic_rank')
                                ->label('الدرجة العلمية')
                                ->required()
                                ->placeholder('مثال: أستاذ، أستاذ مشارك'),
                            TextInput::make('qualification')
                                ->label('المؤهل العلمي')
                                ->required()
                                ->placeholder('مثال: دكتوراه'),
                            TextInput::make('institution')
                                ->label('الجامعة / الكلية / المعهد')
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
                        ->get(['id', 'name', 'academic_rank', 'qualification', 'institution', 'email', 'phone'])
                        ->map(fn (Supervisor $s) => [
                            'id' => $s->id,
                            'name' => $s->name,
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
                        if (! empty($row['id'])) {
                            Supervisor::where('id', $row['id'])->update([
                                'name' => $row['name'],
                                'academic_rank' => $row['academic_rank'],
                                'qualification' => $row['qualification'],
                                'institution' => $row['institution'],
                                'email' => $row['email'] ?? null,
                                'phone' => $row['phone'] ?? null,
                            ]);
                        } else {
                            Supervisor::create([
                                'name' => $row['name'],
                                'academic_rank' => $row['academic_rank'],
                                'qualification' => $row['qualification'],
                                'institution' => $row['institution'],
                                'email' => $row['email'] ?? null,
                                'phone' => $row['phone'] ?? null,
                            ]);
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
