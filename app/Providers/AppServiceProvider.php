<?php

namespace App\Providers;

use App\Models\Application;
use App\Models\Grade;
use App\Models\Student;
use App\Models\ThesisRegistration;
use App\Observers\AuditObserver;
use App\Observers\GradeObserver;
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

        Grade::observe(AuditObserver::class);
        Application::observe(AuditObserver::class);
        Student::observe(AuditObserver::class);
        ThesisRegistration::observe(AuditObserver::class);
    }
}
