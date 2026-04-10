<?php

namespace App\Filament\Resources\Applications\Pages;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Models\Specialization;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;
use Livewire\Attributes\Session;

class ListApplications extends ListRecords
{
    protected static string $resource = ApplicationResource::class;

    protected string $view = 'filament.resources.applications.pages.list-applications';

    #[Session]
    public ?string $scheduledExamDate = null;

    public ?int $activeSpecialization = null;

    public function getSpecializations(): Collection
    {
        return Specialization::orderBy('name')->get();
    }

    public function filterBySpecialization(?int $id): void
    {
        $this->activeSpecialization = $this->activeSpecialization === $id ? null : $id;
        $this->resetTable();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('scheduleExam')
                ->label('تحديد موعد الامتحان')
                ->icon('heroicon-o-calendar')
                ->color('info')
                ->modalHeading('تحديد موعد الامتحان التفاضلي')
                ->modalDescription('سيتم عرض الموعد على الشاشة لاستخدامه عند القبول المبدئي.')
                ->form([
                    DateTimePicker::make('exam_date')
                        ->label('تاريخ الامتحان')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->scheduledExamDate = $data['exam_date'];

                    Notification::make()
                        ->title('تم تحديد موعد الامتحان')
                        ->success()
                        ->send();
                }),

            Action::make('clearExamDate')
                ->label('إلغاء الموعد')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn () => $this->scheduledExamDate !== null)
                ->action(function () {
                    $this->scheduledExamDate = null;
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    public function getSubHeading(): ?string
    {
        if ($this->scheduledExamDate) {
            return 'موعد الامتحان: '.\Carbon\Carbon::parse($this->scheduledExamDate)->format('Y/m/d H:i');
        }

        return null;
    }
}
