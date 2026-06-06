<?php

namespace App\Filament\Widgets;

use App\Enums\ApplicationStatus;
use App\Enums\DiscussionStatus;
use App\Enums\ExceptionalRequestStatus;
use App\Enums\GradeStatus;
use App\Enums\SuspensionStatus;
use App\Enums\ThesisStatus;
use App\Filament\Pages\CoursesSurveyResults;
use App\Filament\Pages\ExceptionalOpportunitiesPage;
use App\Filament\Resources\AddDropRequests\AddDropRequestResource;
use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Resources\DiscussionRequests\DiscussionRequestResource;
use App\Filament\Resources\Grades\GradeResource;
use App\Filament\Resources\SuspensionRequests\SuspensionRequestResource;
use App\Filament\Resources\ThesisRegistrations\ThesisRegistrationResource;
use App\Models\AddDropRequest;
use App\Models\Application;
use App\Models\CourseSurvey;
use App\Models\DiscussionRequest;
use App\Models\ExceptionalRequest;
use App\Models\Grade;
use App\Models\Semester;
use App\Models\SuspensionRequest;
use App\Models\ThesisRegistration;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * "بانتظار إجرائك" — the office head's / secretary's action centre. One clickable
 * card per pending-decision queue, each count mirroring that resource's own
 * getNavigationBadge() so the dashboard and the sidebar never disagree. Zero-count
 * cards are shown greyed (reassurance over hiding).
 *
 * See _brain/project-context/admin-action-center-guide.md.
 */
class ActionCenterWidget extends BaseWidget
{
    protected static ?int $sort = -11;

    protected ?string $heading = 'بانتظار إجرائك';

    public static function canView(): bool
    {
        return auth()->user()?->role !== 'faculty';
    }

    protected function getStats(): array
    {
        $activeSemesterId = Semester::activeId();

        $pendingSurveys = $activeSemesterId
            ? CourseSurvey::where('semester_id', $activeSemesterId)->where('is_processed', false)->count()
            : 0;

        return [
            $this->actionStat(
                'طلبات القبول',
                Application::where('status', ApplicationStatus::Pending)->count(),
                'بانتظار المراجعة',
                'heroicon-o-document-text',
                ApplicationResource::getUrl(),
            ),
            $this->actionStat(
                'طلبات إيقاف القيد',
                SuspensionRequest::where('status', SuspensionStatus::Pending)->count(),
                'بانتظار القرار',
                'heroicon-o-pause-circle',
                SuspensionRequestResource::getUrl(),
            ),
            $this->actionStat(
                'طلبات الإضافة والحذف',
                AddDropRequest::where('status', 'pending')->count(),
                'بانتظار القرار',
                'heroicon-o-arrows-right-left',
                AddDropRequestResource::getUrl(),
            ),
            $this->actionStat(
                'تسجيلات الإشراف',
                ThesisRegistration::where('status', ThesisStatus::Pending)->count(),
                'بانتظار الاعتماد',
                'heroicon-o-academic-cap',
                ThesisRegistrationResource::getUrl(),
            ),
            $this->actionStat(
                'طلبات المناقشة',
                DiscussionRequest::where('status', DiscussionStatus::Pending)->count(),
                'بانتظار الاعتماد',
                'heroicon-o-chat-bubble-left-right',
                DiscussionRequestResource::getUrl(),
            ),
            $this->actionStat(
                'استبيان المقررات',
                $pendingSurveys,
                'بانتظار المعالجة',
                'heroicon-o-clipboard-document-list',
                CoursesSurveyResults::getUrl(),
            ),
            $this->actionStat(
                'الفرص الاستثنائية',
                ExceptionalRequest::where('status', ExceptionalRequestStatus::Pending)->count(),
                'بانتظار القرار',
                'heroicon-o-exclamation-triangle',
                ExceptionalOpportunitiesPage::getUrl(),
            ),
            $this->actionStat(
                'درجات بانتظار الاعتماد',
                Grade::where('status', GradeStatus::PendingApproval)->count(),
                'بانتظار الاعتماد',
                'heroicon-o-clipboard-document-check',
                GradeResource::getUrl(),
            ),
        ];
    }

    private function actionStat(string $label, int $count, string $hint, string $icon, string $url): Stat
    {
        return Stat::make($label, $count)
            ->description($count > 0 ? $hint : 'لا يوجد')
            ->color($count > 0 ? 'warning' : 'gray')
            ->icon($icon)
            ->url($url);
    }
}
