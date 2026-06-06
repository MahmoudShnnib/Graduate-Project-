<?php

namespace App\Observers;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Semester;

class ApplicationObserver
{
    /**
     * Capture which semester was active at the moment this application reached
     * a final decision (Accepted/Rejected). Only sets the value once — never
     * overrides an existing semester_id. Anchors applications to a semester so
     * the archive view can list them under the correct semester record.
     */
    public function saving(Application $application): void
    {
        if ($application->semester_id !== null) {
            return;
        }

        $isDeciding = in_array($application->status, [
            ApplicationStatus::Accepted,
            ApplicationStatus::Rejected,
        ], true);

        if (! $isDeciding) {
            return;
        }

        $activeSemesterId = Semester::activeId();
        if ($activeSemesterId !== null) {
            $application->semester_id = $activeSemesterId;
        }
    }
}
