<?php

namespace App\Filament\Resources\AuditLogs\Pages;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Models\AuditLog;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;

    protected string $view = 'filament.resources.audit-logs.pages.list-audit-logs';

    public string $search = '';

    /** @var array<string> */
    public array $filterActions = [];

    public string $filterUserId = '';

    public string $filterRole = '';

    public string $filterModelType = '';

    public string $filterFrom = '';

    public string $filterTo = '';

    public bool $filterShowSystem = false;

    public int $perPage = 10;

    public int $page = 1;

    /**
     * @return array{items: Collection, total: int, from: int, to: int, lastPage: int}
     */
    public function getAuditLogs(): array
    {
        // Default view collapses each operation (batch) into a single representative row
        // (carrying a `batch_size`); "show system" is the raw, uncollapsed escape hatch.
        $query = $this->filterShowSystem
            ? AuditLog::query()->with('user')
            : AuditLogResource::collapsedQuery();

        $query
            ->when($this->search, fn ($q) => $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$this->search}%")))
            ->when(! empty($this->filterActions), fn ($q) => AuditLogResource::applyActionFilter($q, $this->filterActions))
            ->when($this->filterUserId, fn ($q) => $q->where('user_id', $this->filterUserId))
            ->when($this->filterRole, fn ($q) => $q->whereHas('user', fn ($u) => $u->where('role', $this->filterRole)))
            ->when($this->filterModelType, fn ($q) => $q->where('model_type', $this->filterModelType))
            ->when($this->filterFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->filterFrom))
            ->when($this->filterTo, fn ($q) => $q->whereDate('created_at', '<=', $this->filterTo))
            ->orderByDesc('created_at');

        $total = $query->count();
        $lastPage = (int) max(1, ceil($total / $this->perPage));
        $page = min($this->page, $lastPage);

        $items = $query
            ->skip(($page - 1) * $this->perPage)
            ->take($this->perPage)
            ->get();

        $from = $total > 0 ? ($page - 1) * $this->perPage + 1 : 0;
        $to = min($page * $this->perPage, $total);

        return compact('items', 'total', 'from', 'to', 'lastPage');
    }

    public function toggleAction(string $action): void
    {
        if (in_array($action, $this->filterActions, true)) {
            $this->filterActions = array_values(array_filter($this->filterActions, fn ($a) => $a !== $action));
        } else {
            $this->filterActions[] = $action;
        }

        $this->page = 1;
    }

    public function goToPreviousPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    public function goToNextPage(int $lastPage): void
    {
        if ($this->page < $lastPage) {
            $this->page++;
        }
    }

    public function activeFilterCount(): int
    {
        return (int) (! empty($this->filterActions))
            + (int) ($this->search !== '')
            + (int) ($this->filterUserId !== '')
            + (int) ($this->filterRole !== '')
            + (int) ($this->filterModelType !== '')
            + (int) ($this->filterFrom !== '')
            + (int) ($this->filterTo !== '')
            + (int) $this->filterShowSystem;
    }

    public function resetFilters(): void
    {
        $this->filterActions = [];
        $this->filterUserId = '';
        $this->filterRole = '';
        $this->filterModelType = '';
        $this->filterFrom = '';
        $this->filterTo = '';
        $this->filterShowSystem = false;
        $this->search = '';
        $this->page = 1;
    }

    public function updated(string $property): void
    {
        $filterProperties = [
            'search', 'filterRole', 'filterUserId', 'filterModelType',
            'filterFrom', 'filterTo', 'filterShowSystem', 'perPage',
        ];

        if (in_array($property, $filterProperties, true)) {
            $this->page = 1;
        }
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
