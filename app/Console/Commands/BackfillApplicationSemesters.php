<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Semester;
use Illuminate\Console\Command;

class BackfillApplicationSemesters extends Command
{
    protected $signature = 'applications:backfill-semester';

    protected $description = 'Infer applications.semester_id for already-decided rows from their decision timestamps (exam_result_date or updated_at), finding the semester whose start_date..end_date contains that timestamp.';

    public function handle(): int
    {
        $semesters = Semester::orderBy('start_date')->get();

        if ($semesters->isEmpty()) {
            $this->warn('No semesters exist. Nothing to backfill.');

            return self::SUCCESS;
        }

        $candidates = Application::query()
            ->whereNull('semester_id')
            ->whereIn('status', [ApplicationStatus::Accepted, ApplicationStatus::Rejected])
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('No accepted/rejected applications with NULL semester_id. Nothing to do.');

            return self::SUCCESS;
        }

        $matched = 0;
        $orphaned = 0;

        foreach ($candidates as $application) {
            $decidedAt = $application->exam_result_date ?? $application->updated_at;

            if (! $decidedAt) {
                $orphaned++;

                continue;
            }

            $matchedSemester = $semesters->first(fn (Semester $s) => $decidedAt->between($s->start_date, $s->end_date->endOfDay())
            );

            if (! $matchedSemester) {
                $orphaned++;

                continue;
            }

            $application->updateQuietly(['semester_id' => $matchedSemester->id]);
            $matched++;
        }

        $this->info("Matched: {$matched}");
        if ($orphaned > 0) {
            $this->warn("Orphaned (no semester window covered the decision timestamp): {$orphaned}");
        }

        return self::SUCCESS;
    }
}
