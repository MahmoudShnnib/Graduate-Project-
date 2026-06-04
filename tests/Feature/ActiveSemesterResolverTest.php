<?php

use App\Models\Semester;
use Illuminate\Support\Facades\DB;

it('resolves the single active semester', function () {
    $active = Semester::factory()->create(['is_active' => true]);
    Semester::factory()->create(['is_active' => false]);

    expect(Semester::active()?->id)->toBe($active->id);
    expect(Semester::activeId())->toBe($active->id);
});

it('memoizes the active semester so repeat calls hit the database once', function () {
    $active = Semester::factory()->create(['is_active' => true]);

    // Prime the memo (creation already cleared it via the saved hook).
    Semester::active();

    DB::enableQueryLog();
    Semester::active();
    Semester::active();
    Semester::activeId();
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect($queries)->toBeEmpty();
    expect(Semester::activeId())->toBe($active->id);
});

it('forgets the memo when the active semester changes', function () {
    $first = Semester::factory()->create(['is_active' => true]);
    expect(Semester::activeId())->toBe($first->id);

    // Activating another semester flips the first inactive via SemesterObserver;
    // the save events must invalidate the memo so the next read is fresh.
    $second = Semester::factory()->create(['is_active' => false]);
    $second->update(['is_active' => true]);

    expect(Semester::activeId())->toBe($second->id);
    expect($first->fresh()->is_active)->toBeFalse();
});
