<x-filament-panels::page>
    {{-- ============================================================
         /admin/grades/{offering}/grades — Figma view-page design.
         Page header actions (publish midterms / publish finals) stay
         intact — they're Filament Action objects.
         Backup: _backups/view-course-grades-2026-05-13/
       ============================================================ --}}

    @php
        /** @var \App\Models\CourseOffering $offering */
        $offering = $this->record;
        $courseName = $offering->course?->name ?? '—';
        $courseCode = $offering->course?->code;
        $credits = $offering->course?->credits;
        $isOpen = (bool) $offering->is_open;
        $isArchived = (bool) $offering->semester?->isArchived();
        $semesterName = $offering->semester?->name;
        $facultyName = $offering->faculty?->name ?? 'غير مسند';

        $totalEnrolled = \App\Models\StudentEnrollment::where('course_offering_id', $offering->id)
            ->where('status', \App\Enums\EnrollmentStatus::Enrolled)
            ->count();
        $withGrade = \App\Models\Grade::whereHas('enrollment', fn ($q) => $q
            ->where('course_offering_id', $offering->id)
            ->where('status', \App\Enums\EnrollmentStatus::Enrolled))
            ->count();
        $published = \App\Models\Grade::whereHas('enrollment', fn ($q) => $q
            ->where('course_offering_id', $offering->id)
            ->where('status', \App\Enums\EnrollmentStatus::Enrolled))
            ->whereNotNull('published_at')
            ->count();

        $statusLabel = $isArchived ? 'مؤرشف' : ($isOpen ? 'مفتوح' : 'مغلق');
        $statusColor = $isArchived ? 'gray' : ($isOpen ? 'success' : 'danger');
    @endphp

    <div class="figma-grades-view space-y-6" dir="rtl">

        {{-- HERO CARD --}}
        <section class="fsv-hero">
            <div class="fsv-hero__identity">
                <div class="fsv-hero__name-row">
                    <h1 class="fsv-hero__name">{{ $courseName }}</h1>

                    <span @class([
                        'fsv-status-pill',
                        "fsv-status-pill--{$statusColor}",
                    ])>{{ $statusLabel }}</span>

                    @if ($credits)
                        <span class="fsv-role-pill fsv-role-pill--gray">{{ $credits }} وحدات</span>
                    @endif

                    @if ($published > 0 && $published === $withGrade && $withGrade > 0)
                        <span class="fsv-role-pill fsv-role-pill--success">منشور بالكامل</span>
                    @elseif ($published > 0)
                        <span class="fsv-role-pill fsv-role-pill--info">منشور جزئياً</span>
                    @endif
                </div>

                @if ($courseCode)
                    <div class="fsv-hero__enrollment">
                        <span class="fsv-hero__enrollment-label">رمز المقرر:</span>
                        <span class="fsv-hero__enrollment-value">{{ $courseCode }}</span>
                    </div>
                @endif

                <div class="fsv-hero__meta">
                    @if ($semesterName)
                        <div class="fsv-hero__meta-item">
                            <x-heroicon-o-calendar-days class="fsv-hero__meta-icon" />
                            <span>{{ $semesterName }}</span>
                        </div>
                    @endif

                    <div class="fsv-hero__meta-item">
                        <x-heroicon-o-user class="fsv-hero__meta-icon" />
                        <span>{{ $facultyName }}</span>
                    </div>

                    <div class="fsv-hero__meta-item">
                        <x-heroicon-o-user-group class="fsv-hero__meta-icon" />
                        <span>{{ $totalEnrolled }} طالب مسجل</span>
                    </div>

                    <div class="fsv-hero__meta-item">
                        <x-heroicon-o-clipboard-document-check class="fsv-hero__meta-icon" />
                        <span>{{ $withGrade }} / {{ $totalEnrolled }} درجة مدخلة</span>
                    </div>

                    @if ($published > 0)
                        <div class="fsv-hero__meta-item">
                            <x-heroicon-o-megaphone class="fsv-hero__meta-icon" />
                            <span>{{ $published }} منشورة</span>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        {{-- Grades table --}}
        {{ $this->table }}
    </div>

    @push('styles')
    <style>
        .figma-grades-view .fsv-hero {
            display: flex;
            align-items: flex-start;
            gap: 24px;
            padding: 25px;
            background: #ffffff;
            border: 1px solid #e4e4e7;
            border-radius: 12px;
        }
        .dark .figma-grades-view .fsv-hero {
            background: rgb(24 24 27);
            border-color: rgb(63 63 70);
        }

        .figma-grades-view .fsv-hero__identity {
            display: flex;
            flex-direction: column;
            gap: 7.5px;
            flex: 1;
            min-width: 0;
            align-items: flex-start;
            text-align: right;
        }
        .figma-grades-view .fsv-hero__name-row {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .figma-grades-view .fsv-hero__name-row .fsv-hero__name { margin-inline-end: 12px; }

        .figma-grades-view .fsv-hero__name {
            font-size: 24px;
            line-height: 32px;
            font-weight: 700;
            color: #09090b;
            margin: 0;
        }
        .dark .figma-grades-view .fsv-hero__name { color: #fafafa; }

        .figma-grades-view .fsv-hero__enrollment {
            display: inline-flex;
            align-items: baseline;
            gap: 8px;
            margin-top: 2px;
        }
        .figma-grades-view .fsv-hero__enrollment-label {
            font-size: 14px;
            color: #09090b;
            font-weight: 500;
        }
        .dark .figma-grades-view .fsv-hero__enrollment-label { color: #fafafa; }
        .figma-grades-view .fsv-hero__enrollment-value {
            font-size: 22px;
            font-weight: 700;
            color: #09090b;
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.01em;
        }
        .dark .figma-grades-view .fsv-hero__enrollment-value { color: #fafafa; }

        /* Muted pills */
        .figma-grades-view .fsv-status-pill {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border-radius: 4px;
            font-size: 12px;
            line-height: 16px;
            font-weight: 700;
        }
        .figma-grades-view .fsv-status-pill--success { background: #f0fdf4; border: 1px solid #dcfce7; color: #166534; }
        .figma-grades-view .fsv-status-pill--danger  { background: #fef2f2; border: 1px solid #fee2e2; color: #991b1b; }
        .figma-grades-view .fsv-status-pill--warning { background: #fffbeb; border: 1px solid #fef3c7; color: #92400e; }
        .figma-grades-view .fsv-status-pill--info    { background: #eff6ff; border: 1px solid #dbeafe; color: #1e40af; }
        .figma-grades-view .fsv-status-pill--gray    { background: #fafafa; border: 1px solid #e4e4e7; color: #52525b; }
        .dark .figma-grades-view .fsv-status-pill--success { background: rgba(34, 197, 94, 0.08); border-color: rgba(34, 197, 94, 0.18); color: #86efac; }
        .dark .figma-grades-view .fsv-status-pill--danger  { background: rgba(239, 68, 68, 0.08); border-color: rgba(239, 68, 68, 0.18); color: #fca5a5; }
        .dark .figma-grades-view .fsv-status-pill--warning { background: rgba(245, 158, 11, 0.08); border-color: rgba(245, 158, 11, 0.18); color: #fcd34d; }
        .dark .figma-grades-view .fsv-status-pill--info    { background: rgba(59, 130, 246, 0.08); border-color: rgba(59, 130, 246, 0.18); color: #93c5fd; }

        .figma-grades-view .fsv-role-pill {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border-radius: 4px;
            font-size: 12px;
            line-height: 16px;
            font-weight: 600;
            background: #f4f4f5;
            border: 1px solid #e4e4e7;
            color: #52525b;
        }
        .figma-grades-view .fsv-role-pill--success { color: #166534; border-color: #dcfce7; background: #f0fdf4; }
        .figma-grades-view .fsv-role-pill--info    { color: #1e40af; border-color: #dbeafe; background: #eff6ff; }
        .dark .figma-grades-view .fsv-role-pill {
            background: rgba(244, 244, 245, 0.06);
            border-color: rgb(63 63 70);
            color: #d4d4d8;
        }
        .dark .figma-grades-view .fsv-role-pill--success { color: #86efac; background: rgba(34, 197, 94, 0.08); border-color: rgba(34, 197, 94, 0.18); }
        .dark .figma-grades-view .fsv-role-pill--info    { color: #93c5fd; background: rgba(59, 130, 246, 0.08); border-color: rgba(59, 130, 246, 0.18); }

        .figma-grades-view .fsv-hero__meta {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .figma-grades-view .fsv-hero__meta-item {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 14px;
            line-height: 20px;
            color: #71717a;
        }
        .figma-grades-view .fsv-hero__meta-icon { width: 14px; height: 14px; color: #71717a; }

        /* Table wrapped as a card */
        .figma-grades-view .fi-ta-ctn {
            background: #ffffff;
            border: 1px solid #e4e4e7;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: none;
        }
        .dark .figma-grades-view .fi-ta-ctn {
            background: rgb(24 24 27);
            border-color: rgb(63 63 70);
        }
        .figma-grades-view .fi-ta-header {
            background: rgba(244, 244, 245, 0.5);
            border-bottom: 1px solid #e4e4e7;
            padding: 12px 20px 13px;
        }
        .dark .figma-grades-view .fi-ta-header {
            background: rgba(63, 63, 70, 0.3);
            border-bottom-color: rgb(63 63 70);
        }
    </style>
    @endpush
</x-filament-panels::page>
