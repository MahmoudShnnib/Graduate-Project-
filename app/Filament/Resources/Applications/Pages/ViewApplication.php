<?php

namespace App\Filament\Resources\Applications\Pages;

use App\Filament\Resources\Applications\ApplicationResource;
use Filament\Resources\Pages\ViewRecord;
use Livewire\Attributes\Session;

class ViewApplication extends ViewRecord
{
    protected static string $resource = ApplicationResource::class;

    protected string $view = 'filament.resources.applications.pages.view-application';

    #[Session(key: 'applications.scheduled_exam_date')]
    public ?string $scheduledExamDate = null;

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return '';
    }

    public function getBreadcrumb(): string
    {
        return $this->record->name ?? (string) $this->record->getKey();
    }

    public function getSubHeading(): ?string
    {
        if ($this->scheduledExamDate) {
            return 'موعد الامتحان: '.\Carbon\Carbon::parse($this->scheduledExamDate)->format('Y/m/d H:i');
        }

        return null;
    }

    protected function getHeaderActions(): array
    {
        return [
            ApplicationResource::scheduleExamAction(),
            ApplicationResource::clearExamDateAction(),
            ApplicationResource::initialApproveAction(),
            ApplicationResource::directApproveAction(),
        ];
    }
}
