<?php

namespace App\Filament\Resources\Applications\Pages;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Models\Application;
use App\Models\Semester;
use App\Models\Specialization;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Session;

class ListApplications extends ListRecords
{
    protected static string $resource = ApplicationResource::class;

    protected string $view = 'filament.resources.applications.pages.list-applications';

    #[Session(key: 'applications.scheduled_exam_date')]
    public ?string $scheduledExamDate = null;

    /** Semester the scheduled exam date belongs to, so it auto-resets on a new term. */
    #[Session(key: 'applications.scheduled_exam_semester_id')]
    public ?int $scheduledExamSemesterId = null;

    public ?int $activeSpecialization = null;

    public function mount(): void
    {
        parent::mount();

        // A new active semester invalidates any exam date set for the previous one.
        $activeSemesterId = Semester::activeId();

        if ($this->scheduledExamSemesterId !== $activeSemesterId) {
            $this->scheduledExamDate = null;
            $this->scheduledExamSemesterId = $activeSemesterId;
        }
    }

    public function getSpecializations(): Collection
    {
        return Specialization::orderBy('name')->get();
    }

    /**
     * Specialization tabs whose counts follow the table's current semester filter:
     * the filter defaults to the active semester (so a new term reads 0), and the
     * counts reflect all semesters when the admin clears that filter.
     *
     * @return list<array{id: ?int, label: string, count: int}>
     */
    public function getSpecializationTabs(): array
    {
        $semesterId = $this->tableFilters['semester_id']['value'] ?? null;

        $scoped = fn (): Builder => Application::query()
            ->when($semesterId, fn (Builder $query) => $query->where('semester_id', $semesterId));

        $tabs = [['id' => null, 'label' => 'الكل', 'count' => $scoped()->count()]];

        foreach ($this->getSpecializations() as $spec) {
            $tabs[] = [
                'id' => $spec->id,
                'label' => $spec->name,
                'count' => $scoped()->where('specialization_id', $spec->id)->count(),
            ];
        }

        return $tabs;
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
