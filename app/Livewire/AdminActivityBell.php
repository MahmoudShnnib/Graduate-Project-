<?php

namespace App\Livewire;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;

class AdminActivityBell extends Component
{
    /** @var array<int, array<string, mixed>> */
    public array $activities = [];

    public int $badgeCount = 0;

    /**
     * Roles whose activity the super-admin monitors. (Their own actions are excluded
     * separately so the bell only ever shows what *other* people did.)
     */
    private const WATCHED_ROLES = ['super_admin', 'manager_assistant'];

    public function mount(): void
    {
        $this->refresh();
    }

    /**
     * Recompute the unseen, collapsed activity list + badge. Runs on mount and each poll.
     */
    public function refresh(): void
    {
        $lastSeen = (int) (auth()->user()?->last_seen_activity_id ?? 0);

        $rows = $this->collapse(
            $this->baseQuery()->where('id', '>', $lastSeen)->limit(60)->get()
        );

        $this->activities = $rows->map(fn (AuditLog $log): array => $this->describe($log))->all();
        $this->badgeCount = $rows->count();
    }

    /**
     * Mark everything up to the latest entry as seen (clears the badge). Already-shown
     * items stay in $activities so they remain visible until the next refresh/poll.
     */
    public function markSeen(): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $latestId = (int) ($this->baseQuery()->max('id') ?? 0);

        if ($latestId > (int) ($user->last_seen_activity_id ?? 0)) {
            $user->last_seen_activity_id = $latestId;
            $user->save();
        }

        $this->badgeCount = 0;
    }

    private function baseQuery(): Builder
    {
        return AuditLog::query()
            ->whereHas('user', fn (Builder $query) => $query->whereIn('role', self::WATCHED_ROLES))
            ->where('user_id', '!=', auth()->id())
            ->with('user')
            ->latest('id');
    }

    /**
     * Collapse the storage rows for display only: when an operation (batch) produced a
     * decision, hide the generic create/update/delete rows it also wrote — the full set
     * still lives in the audit log. Also dedupes a record touched twice in one batch.
     *
     * @param  Collection<int, AuditLog>  $rows
     * @return Collection<int, AuditLog>
     */
    private function collapse(Collection $rows): Collection
    {
        $decisionBatches = $rows
            ->filter(fn (AuditLog $log): bool => ! $this->isGeneric($log->action) && $log->batch_id !== null)
            ->pluck('batch_id')
            ->flip();

        $seen = [];

        return $rows->filter(function (AuditLog $log) use ($decisionBatches, &$seen): bool {
            if (! $this->isGeneric($log->action)) {
                return true;
            }

            if ($log->batch_id !== null && $decisionBatches->has($log->batch_id)) {
                return false;
            }

            $key = $log->batch_id.'|'.$log->model_type.'|'.$log->model_id.'|'.$log->action;

            if (isset($seen[$key])) {
                return false;
            }

            $seen[$key] = true;

            return true;
        })->values();
    }

    private function isGeneric(string $action): bool
    {
        return (bool) preg_match('/_(created|updated|deleted)$/', $action);
    }

    /**
     * @return array<string, mixed>
     */
    private function describe(AuditLog $log): array
    {
        $label = AuditService::actionLabel($log->action);
        $subject = $log->subject_label;

        [$type, $icon, $color] = $this->presentation($log->action);

        $title = $type === 'decision'
            ? $label.($subject ? ' — '.$subject : '')
            : $label.($subject ? ': '.$subject : '');

        return [
            'id' => $log->id,
            'title' => $title,
            'icon' => $icon,
            'color' => $color,
            'user' => $log->user?->name ?? '—',
            'initial' => mb_substr($log->user?->name ?? 'N', 0, 1),
            'role' => $log->user?->role ?? '',
            'time' => $log->created_at?->diffForHumans(short: true) ?? '',
            'url' => AuditLogResource::getUrl('view', ['record' => $log->getKey()]),
        ];
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function presentation(string $action): array
    {
        return match (true) {
            str_ends_with($action, '_created') => ['created', 'heroicon-o-plus-circle', 'text-emerald-500'],
            str_ends_with($action, '_updated') => ['updated', 'heroicon-o-pencil-square', 'text-amber-500'],
            str_ends_with($action, '_deleted') => ['deleted', 'heroicon-o-trash', 'text-red-500'],
            default => ['decision', 'heroicon-o-check-circle', 'text-blue-500'],
        };
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.admin-activity-bell');
    }
}
