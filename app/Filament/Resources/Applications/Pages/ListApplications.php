<?php

namespace App\Filament\Resources\Applications\Pages;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Models\Specialization;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;
use Livewire\Attributes\Session;

class ListApplications extends ListRecords
{
    protected static string $resource = ApplicationResource::class;

    protected string $view = 'filament.resources.applications.pages.list-applications';

    #[Session(key: 'applications.scheduled_exam_date')]
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
            ApplicationResource::scheduleExamAction(),
            ApplicationResource::clearExamDateAction(),
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
