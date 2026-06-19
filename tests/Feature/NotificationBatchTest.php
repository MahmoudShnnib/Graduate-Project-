<?php

use App\Filament\Resources\NotificationLogs\NotificationLogResource;
use App\Models\NotificationLog;
use App\Models\Setting;
use App\Models\Student;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config()->set('services.qonvo.api_token', 'q-token');
    config()->set('services.qonvo.sender_phone', '0911111111');
    config()->set('services.telegram.bot_token', 't-token');
    config()->set('services.telegram.bot_username', 'TestBot');
    Setting::flushCache();
    Http::fake([
        'qonvo.ly/*' => Http::response(['data' => ['status' => 'sent', 'id' => 'wx']], 200),
        'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]], 200),
    ]);
});

function batchStudent(string $phone): Student
{
    $record = studentWithRecord();
    // saveQuietly so changing the phone doesn't fire UserObserver's contact-change alert.
    $record['user']->forceFill(['phone' => $phone, 'telegram_chat_id' => 'tg-'.$phone])->saveQuietly();

    return $record['student'];
}

it('groups a broadcast of many students under a single batch id', function () {
    $svc = app(NotificationService::class);
    $s1 = batchStudent('0911111111');
    $s2 = batchStudent('0922222222');

    $svc->batch(function () use ($svc, $s1, $s2) {
        $svc->notifyGradesPublished($s1);
        $svc->notifyGradesPublished($s2);
    });

    // 2 students x 2 channels = 4 rows, all sharing ONE batch id.
    expect(NotificationLog::count())->toBe(4)
        ->and(NotificationLog::pluck('batch_id')->unique()->values())->toHaveCount(1)
        ->and(NotificationLog::whereNull('batch_id')->count())->toBe(0);
});

it('does NOT batch a standalone send — only broadcasts get a batch id', function () {
    $svc = app(NotificationService::class);

    $svc->batch(fn () => $svc->notifyGradesPublished(batchStudent('0911111111'))); // broadcast
    $svc->notifyGradesPublished(batchStudent('0922222222')); // standalone single

    expect(NotificationLog::whereNotNull('batch_id')->pluck('batch_id')->unique())->toHaveCount(1) // one broadcast
        ->and(NotificationLog::whereNull('batch_id')->count())->toBe(2); // the single's 2 channel rows stay ungrouped
});

function seedBatch(string $batchId, string $student): void
{
    foreach (['sent', 'sent', 'failed'] as $status) {
        NotificationLog::create([
            'batch_id' => $batchId,
            'student_id' => $student,
            'channel' => 'telegram',
            'phone' => '0911111111',
            'event' => 'grades_published',
            'message' => 'msg',
            'status' => $status,
            'sent_at' => now(),
            'created_at' => now(),
        ]);
    }
}

it('summarises a batch delivery counts', function () {
    $student = studentWithRecord()['student'];
    seedBatch('batch-1', $student->id);

    expect(NotificationLogResource::batchStatusLine('batch-1'))
        ->toContain('✓ 2')->toContain('✗ 1');
});

it('collapses a broadcast into one summary row in the notification-logs resource', function () {
    $student = studentWithRecord()['student'];
    seedBatch('batch-1', $student->id); // 3 telegram rows: 2 sent, 1 failed

    $this->actingAs(adminUser())
        ->get('/admin/notification-logs')
        ->assertOk()
        ->assertSee('بث جماعي')    // the broadcast marker (not 3 separate rows)
        ->assertSee('نشر الدرجات') // the event
        ->assertSee('✓ 2');        // the delivery summary
});
