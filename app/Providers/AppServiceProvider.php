<?php

namespace App\Providers;

use App\Models\Application;
use App\Models\CourseOffering;
use App\Models\Grade;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\ThesisRegistration;
use App\Observers\ApplicationObserver;
use App\Observers\AuditObserver;
use App\Observers\CourseOfferingObserver;
use App\Observers\GradeObserver;
use App\Observers\SemesterObserver;
use App\Observers\StudentObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::before(function ($user, string $ability): ?bool {
            return $user->hasRole('super_admin') ? true : null;
        });

        Grade::observe(GradeObserver::class);
        Student::observe(StudentObserver::class);
        CourseOffering::observe(CourseOfferingObserver::class);
        Semester::observe(SemesterObserver::class);
        Application::observe(ApplicationObserver::class);

        Grade::observe(AuditObserver::class);
        Application::observe(AuditObserver::class);
        Student::observe(AuditObserver::class);
        ThesisRegistration::observe(AuditObserver::class);
        StudentEnrollment::observe(AuditObserver::class);
    }
}
