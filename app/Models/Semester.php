<?php

namespace App\Models;

use App\Enums\Season;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semester extends Model
{
    use HasFactory;

    private static ?self $activeMemo = null;

    private static bool $activeResolved = false;

    protected $fillable = [
        'name',
        'year',
        'season',
        'start_date',
        'end_date',
        'add_drop_start',
        'add_drop_end',
        'is_active',
        'survey_open',
        'applications_open',
        'application_deadline',
        'archived_at',
    ];

    /**
     * The single active semester (is_active=true), memoized for the request.
     * Under the single-active-semester invariant this is the canonical "current
     * term" lookup. The memo is cleared on any semester save/delete (see booted()),
     * so reads after an active-flip cascade stay correct within the same request.
     */
    public static function active(): ?self
    {
        if (! static::$activeResolved) {
            static::$activeMemo = static::query()->where('is_active', true)->first();
            static::$activeResolved = true;
        }

        return static::$activeMemo;
    }

    /**
     * Id of the active semester, or null when none is active.
     */
    public static function activeId(): ?int
    {
        return static::active()?->id;
    }

    /**
     * Clear the memoized active-semester lookup. Fired on any save/delete so reads
     * stay correct within a request; also called between tests, where the static
     * would otherwise leak across cases in the same process.
     */
    public static function flushActiveCache(): void
    {
        static::$activeMemo = null;
        static::$activeResolved = false;
    }

    protected static function booted(): void
    {
        static::saved(static fn () => static::flushActiveCache());
        static::deleted(static fn () => static::flushActiveCache());
    }

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'season' => Season::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'add_drop_start' => 'date',
            'add_drop_end' => 'date',
            'is_active' => 'boolean',
            'survey_open' => 'boolean',
            'applications_open' => 'boolean',
            'application_deadline' => 'date',
            'archived_at' => 'datetime',
        ];
    }

    /**
     * Whether this semester currently accepts new public applications: the master
     * switch is on AND no deadline has passed (a null deadline means no auto-close).
     */
    public function acceptsApplications(): bool
    {
        if (! $this->applications_open) {
            return false;
        }

        if ($this->application_deadline === null) {
            return true;
        }

        return now()->toDateString() <= $this->application_deadline->toDateString();
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'semester_course')
            ->withTimestamps();
    }

    public function courseOfferings(): HasMany
    {
        return $this->hasMany(CourseOffering::class);
    }

    public function surveys(): HasMany
    {
        return $this->hasMany(CourseSurvey::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function suspensionRequests(): HasMany
    {
        return $this->hasMany(SuspensionRequest::class);
    }

    public function isAddDropOpen(): bool
    {
        if (! $this->add_drop_start || ! $this->add_drop_end) {
            return false;
        }

        $today = now()->toDateString();

        return $today >= $this->add_drop_start->toDateString()
            && $today <= $this->add_drop_end->toDateString();
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * Mark this semester as archived in one atomic step: set archived_at,
     * flip is_active and survey_open off, and close every open offering in
     * the semester via a query-builder mass update (which intentionally
     * bypasses CourseOfferingObserver so existing enrollments + grades
     * stay as historical data). Idempotent — calling on an already-archived
     * semester is a no-op.
     */
    public function archiveCascade(): void
    {
        if ($this->isArchived()) {
            return;
        }

        \DB::transaction(function (): void {
            $this->update([
                'archived_at' => now(),
                'is_active' => false,
                'survey_open' => false,
                'applications_open' => false,
            ]);

            $this->courseOfferings()
                ->where('is_open', true)
                ->update(['is_open' => false]);
        });
    }
}
