<?php

use App\Models\AuditLog;
use App\Models\Semester;
use App\Models\Specialization;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

it('resetBatch starts a fresh operation id (Tier 1 — no cross-operation glue)', function () {
    $first = AuditService::batchId();
    AuditService::resetBatch();
    $second = AuditService::batchId();

    expect($first)->not->toBe($second);
});

it('stamps the active semester on every audit log (Tier 3)', function () {
    $semester = Semester::factory()->create(['is_active' => true]);
    Semester::flushActiveCache();

    $spec = Specialization::factory()->create();

    $log = AuditLog::where('model_type', Specialization::class)
        ->where('model_id', $spec->id)
        ->first();

    expect($log->semester_id)->toBe($semester->id);
});

it('archives a semester logs into cold storage and clears them from the live table', function () {
    $semester = Semester::factory()->create(['is_active' => true]);
    Semester::flushActiveCache();
    Specialization::factory()->create();

    $before = AuditLog::where('semester_id', $semester->id)->count();
    expect($before)->toBeGreaterThan(0);

    $moved = AuditService::archiveSemesterLogs($semester->id);

    expect($moved)->toBe($before)
        ->and(AuditLog::where('semester_id', $semester->id)->count())->toBe(0)
        ->and(DB::table('audit_log_archives')->where('semester_id', $semester->id)->count())->toBe($before);
});

it('moves a semester logs to cold storage when the semester is archived (archiveCascade)', function () {
    $semester = Semester::factory()->create(['is_active' => true]);
    Semester::flushActiveCache();
    Specialization::factory()->create();

    expect(AuditLog::where('semester_id', $semester->id)->count())->toBeGreaterThan(0);

    $semester->archiveCascade();

    expect(AuditLog::where('semester_id', $semester->id)->count())->toBe(0)
        ->and(DB::table('audit_log_archives')->where('semester_id', $semester->id)->count())->toBeGreaterThan(0);
});
