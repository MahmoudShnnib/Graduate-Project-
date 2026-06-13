<?php

namespace App\Providers;

use App\Filament\Auth\Responses\LoginResponse;
use App\Models\Application;
use App\Models\CourseOffering;
use App\Models\Grade;
use App\Models\Semester;
use App\Models\Student;
use App\Observers\ApplicationObserver;
use App\Observers\AuditObserver;
use App\Observers\CourseOfferingObserver;
use App\Observers\GradeObserver;
use App\Observers\SemesterObserver;
use App\Observers\StudentObserver;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LoginResponseContract::class, LoginResponse::class);
    }

    public function boot(): void
    {
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Gate::before(function ($user, string $ability): ?bool {
            return $user->hasRole('super_admin') ? true : null;
        });

        // Give each unit of work (queued job / console command) its own audit batch id,
        // mirroring the per-request reset done by the ResetAuditBatch middleware.
        Event::listen(\Illuminate\Queue\Events\JobProcessing::class, fn () => \App\Services\AuditService::resetBatch());
        Event::listen(\Illuminate\Console\Events\CommandStarting::class, fn () => \App\Services\AuditService::resetBatch());

        // Lock every Edit page's "Save changes" button until the user actually changes
        // something, then unlock on the first real input/change and re-lock after a
        // submit. We avoid the button's `disabled` attribute (Filament's own Alpine
        // `filamentFormButton` already owns it for the loading state) and instead toggle
        // a CSS class — driven by DOM events, which survive Filament's reactive field
        // syncs (so it never "flashes"), and re-applied after every Livewire morph.
        // Applies to all resources, including Shield's Roles edit page.
        FilamentView::registerRenderHook(PanelsRenderHook::BODY_END, fn (): string => $this->saveButtonGuard());

        Grade::observe(GradeObserver::class);
        Student::observe(StudentObserver::class);
        CourseOffering::observe(CourseOfferingObserver::class);
        Semester::observe(SemesterObserver::class);
        Application::observe(ApplicationObserver::class);

        // Full create/update/delete audit trail across every admin-managed record.
        // (AuditLog/NotificationLog/WhatsAppOtp are intentionally excluded — logging
        // those would recurse or just record transient noise.)
        foreach ([
            \App\Models\Application::class,
            \App\Models\Student::class,
            \App\Models\User::class,
            \App\Models\Grade::class,
            \App\Models\StudentEnrollment::class,
            \App\Models\ThesisRegistration::class,
            \App\Models\SuspensionRequest::class,
            \App\Models\AddDropRequest::class,
            \App\Models\DiscussionRequest::class,
            \App\Models\ExceptionalRequest::class,
            \App\Models\ClearanceRequest::class,
            \App\Models\CourseSurvey::class,
            \App\Models\Course::class,
            \App\Models\CourseOffering::class,
            \App\Models\Semester::class,
            \App\Models\Specialization::class,
            \App\Models\TransferCourse::class,
            \App\Models\StudentWarning::class,
            \App\Models\Supervisor::class,
            \App\Models\Setting::class,
        ] as $auditedModel) {
            $auditedModel::observe(AuditObserver::class);
        }
    }

    /**
     * Inline style + script that greys/locks the Edit-page save button until the form
     * is changed. Targets edit forms only (`wire:submit="save"`); the lock state is kept
     * per-form and re-applied on Livewire morphs so reactive fields don't unlock it.
     */
    private function saveButtonGuard(): string
    {
        return <<<'HTML'
        <style>[data-save-locked]{opacity:.55!important;pointer-events:none!important;}</style>
        <script>
        (function(){
            function submitBtn(form){return form.querySelector('button[type="submit"]');}
            function refresh(form){
                var b=submitBtn(form); if(!b){return;}
                if(form.__saveChanged){b.removeAttribute('data-save-locked');}
                else{b.setAttribute('data-save-locked','');}
            }
            function guard(form){
                if(form.__saveGuard){return;} form.__saveGuard=true; form.__saveChanged=false;
                form.addEventListener('input',function(){form.__saveChanged=true;refresh(form);});
                form.addEventListener('change',function(){form.__saveChanged=true;refresh(form);});
                // Filament's Toggle and Select render as buttons, not native
                // inputs, so they never emit input/change on the form. Treat any
                // click on a form control (but not the submit button itself) as an
                // edit, so select/toggle-only forms can't deadlock the Save button.
                form.addEventListener('click',function(e){
                    if(e.target.closest('button[type="submit"]')){return;}
                    form.__saveChanged=true;refresh(form);
                });
                form.addEventListener('submit',function(){form.__saveChanged=false;setTimeout(function(){refresh(form);},50);});
            }
            function scan(){document.querySelectorAll('form[wire\\:submit="save"]').forEach(function(f){guard(f);refresh(f);});}
            document.addEventListener('DOMContentLoaded',scan);
            document.addEventListener('livewire:navigated',scan);
            document.addEventListener('livewire:init',function(){
                if(window.Livewire&&Livewire.hook){Livewire.hook('morphed',scan);}
            });
        })();
        </script>
        HTML;
    }
}
