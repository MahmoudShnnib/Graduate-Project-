<?php

namespace App\Filament\Widgets;

use App\Enums\ApplicationStatus;
use App\Enums\DiscussionStatus;
use App\Enums\GradeStatus;
use App\Enums\StudentStatus;
use App\Enums\SuspensionStatus;
use App\Enums\ThesisStatus;
use App\Filament\Resources\AddDropRequests\AddDropRequestResource;
use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Resources\DiscussionRequests\DiscussionRequestResource;
use App\Filament\Resources\Grades\GradeResource;
use App\Filament\Resources\SuspensionRequests\SuspensionRequestResource;
use App\Filament\Resources\ThesisRegistrations\ThesisRegistrationResource;
use App\Models\AddDropRequest;
use App\Models\Application;
use App\Models\DiscussionRequest;
use App\Models\Grade;
use App\Models\Semester;
use App\Models\Student;
use App\Models\SuspensionRequest;
use App\Models\ThesisRegistration;
use Filament\Widgets\Widget;

class DashboardOverviewWidget extends Widget
{
    protected string $view = 'filament.widgets.dashboard-overview';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->role !== 'faculty';
    }

    /**
     * @return array{
     *     semesterName: string,
     *     addDropOpen: bool,
     *     kpis: array<int, array<string, mixed>>,
     *     funnel: array<string, mixed>,
     *     attention: array<int, array<string, mixed>>,
     * }
     */
    public function getDashboardData(): array
    {
        $activeSemester = Semester::where('is_active', true)->first();
        $semesterId = $activeSemester?->id;

        $appsInSemester = fn () => Application::query()
            ->when($semesterId, fn ($q) => $q->where('semester_id', $semesterId));

        // ---- Admissions funnel (active semester) ----
        $submitted = $appsInSemester()->count();
        $accepted = $appsInSemester()->where('status', ApplicationStatus::Accepted)->count();
        $rejected = $appsInSemester()->where('status', ApplicationStatus::Rejected)->count();
        $initialApproval = $appsInSemester()
            ->whereIn('status', [ApplicationStatus::InitiallyApproved, ApplicationStatus::Accepted])
            ->count();
        $pendingApps = $appsInSemester()->where('status', ApplicationStatus::Pending)->count();

        $pct = fn (int $part, int $whole): int => $whole > 0 ? (int) round($part / $whole * 100) : 0;

        $funnel = [
            'rejected' => $rejected,
            'stages' => [
                [
                    'label' => 'الطلبات المقدمة',
                    'count' => $submitted,
                    'width' => 100,
                    'conversion' => null,
                ],
                [
                    'label' => 'قبول مبدئي (دعوة للامتحان)',
                    'count' => $initialApproval,
                    'width' => $pct($initialApproval, $submitted),
                    'conversion' => $pct($initialApproval, $submitted),
                ],
                [
                    'label' => 'قبول نهائي',
                    'count' => $accepted,
                    'width' => $pct($accepted, $submitted),
                    'conversion' => $pct($accepted, $initialApproval),
                ],
            ],
        ];

        // ---- KPI cards ----
        $activeStudents = Student::where('status', StudentStatus::Active)->count();
        $onHold = Student::where('status', StudentStatus::OnHold)->count();
        $inactive = Student::where('status', StudentStatus::Inactive)->count();
        $transferred = Student::where('status', StudentStatus::Transferred)->count();
        $dismissed = Student::where('status', StudentStatus::Dismissed)->count();

        $kpis = [
            [
                'label' => 'الطلاب المستمرون',
                'value' => (string) $activeStudents,
                'sub' => "موقوف {$onHold} · منقطع {$inactive} · منتقل {$transferred} · مفصول {$dismissed}",
                'icon' => 'heroicon-o-user-group',
                'tone' => 'emerald',
            ],
            [
                'label' => 'طلبات هذا الفصل',
                'value' => (string) $submitted,
                'sub' => "مقبول {$accepted} · مرفوض {$rejected}",
                'icon' => 'heroicon-o-document-text',
                'tone' => 'sky',
            ],
            [
                'label' => 'نسبة القبول',
                'value' => $pct($accepted, $submitted).'%',
                'sub' => 'من إجمالي طلبات الفصل',
                'icon' => 'heroicon-o-chart-pie',
                'tone' => 'violet',
            ],
            [
                'label' => 'الفصل الحالي',
                'value' => $activeSemester?->name ?? 'لا يوجد',
                'sub' => 'فترة الإضافة/الحذف: '.($activeSemester?->isAddDropOpen() ? 'مفتوحة' : 'مغلقة'),
                'icon' => 'heroicon-o-calendar',
                'tone' => $activeSemester ? 'indigo' : 'rose',
            ],
        ];

        // ---- Needs-attention hub ----
        $attention = [
            [
                'label' => 'طلبات قبول معلّقة',
                'count' => $pendingApps,
                'url' => ApplicationResource::getUrl('index'),
                'icon' => 'heroicon-o-inbox-arrow-down',
                'tone' => 'amber',
            ],
            [
                'label' => 'درجات بانتظار الاعتماد',
                'count' => Grade::where('status', GradeStatus::PendingApproval)->count(),
                'url' => GradeResource::getUrl('index'),
                'icon' => 'heroicon-o-clipboard-document-check',
                'tone' => 'sky',
            ],
            [
                'label' => 'طلبات إضافة/حذف',
                'count' => AddDropRequest::where('status', 'pending')->count(),
                'url' => AddDropRequestResource::getUrl('index'),
                'icon' => 'heroicon-o-arrows-right-left',
                'tone' => 'violet',
            ],
            [
                'label' => 'طلبات مناقشة',
                'count' => DiscussionRequest::where('status', DiscussionStatus::Pending)->count(),
                'url' => DiscussionRequestResource::getUrl('index'),
                'icon' => 'heroicon-o-chat-bubble-left-right',
                'tone' => 'rose',
            ],
            [
                'label' => 'طلبات تسجيل أطروحة',
                'count' => ThesisRegistration::where('status', ThesisStatus::Pending)->count(),
                'url' => ThesisRegistrationResource::getUrl('index'),
                'icon' => 'heroicon-o-academic-cap',
                'tone' => 'indigo',
            ],
            [
                'label' => 'طلبات إيقاف قيد',
                'count' => SuspensionRequest::where('status', SuspensionStatus::Pending)->count(),
                'url' => SuspensionRequestResource::getUrl('index'),
                'icon' => 'heroicon-o-pause-circle',
                'tone' => 'emerald',
            ],
        ];

        return [
            'semesterName' => $activeSemester?->name ?? 'لا يوجد فصل نشط',
            'addDropOpen' => (bool) $activeSemester?->isAddDropOpen(),
            'kpis' => $kpis,
            'funnel' => $funnel,
            'attention' => $attention,
        ];
    }
}
