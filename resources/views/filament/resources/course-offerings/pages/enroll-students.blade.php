<x-filament-panels::page>
    {{-- ============================================================
         /admin/course-offerings/{id}/enroll — Figma view-page design.
         Workflow actions (toggle/print/survey/retake/enroll) stay in
         the page header — they're Filament Action objects.
         Backup: _backups/enroll-students-2026-05-13/
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
        $facultyName = $offering->faculty?->name;
        $enrolledCount = $offering->activeEnrollments()->count();

        $statusLabel = $isArchived ? 'مؤرشف' : ($isOpen ? 'مفتوح للتسجيل' : 'مغلق');
        $statusColor = $isArchived ? 'gray' : ($isOpen ? 'success' : 'danger');
    @endphp

    @php
        $isAdmin = in_array(auth()->user()?->role, ['super_admin', 'secretary'], true);
        $isCourseFaculty = auth()->user()?->role === 'faculty' && $offering->faculty_id === auth()->id();
        $canPrint = $isAdmin || $isCourseFaculty;
    @endphp

    <div class="figma-enroll-view space-y-6" dir="rtl">

        {{-- HERO CARD --}}
        <section class="fsv-hero">

            @if ($canPrint)
                <div class="fsv-hero__actions">
                    <div class="fsv-print-menu" x-data="{ open: false }" @click.outside="open = false" style="position: relative;">
                        <button type="button" @click="open = ! open" class="fsv-btn fsv-btn--secondary">
                            <span>طباعة</span>
                            <x-heroicon-o-printer class="fsv-btn__icon" />
                        </button>
                        <div x-show="open" x-cloak x-transition class="fsv-print-menu__panel">
                            <a href="{{ route('print.exam-attendance', $offering) }}" target="_blank" rel="noopener" class="fsv-print-menu__item" @click="open = false">
                                <x-heroicon-o-clipboard-document-list class="fsv-print-menu__icon" />
                                <span>كشف حضور الإمتحان النهائي</span>
                            </a>
                            <a href="{{ route('print.course-results', $offering) }}" target="_blank" rel="noopener" class="fsv-print-menu__item" @click="open = false">
                                <x-heroicon-o-chart-bar class="fsv-print-menu__icon" />
                                <span>نتيجة مقرر دراسي</span>
                            </a>
                            @if ($isAdmin)
                                <a href="{{ route('course-offering.print-students', $offering) }}" target="_blank" rel="noopener" class="fsv-print-menu__item" @click="open = false">
                                    <x-heroicon-o-users class="fsv-print-menu__icon" />
                                    <span>نموذج مقرر دراسي</span>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

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

                    @if ($facultyName)
                        <div class="fsv-hero__meta-item">
                            <x-heroicon-o-user class="fsv-hero__meta-icon" />
                            <span>{{ $facultyName }}</span>
                        </div>
                    @endif

                    <div class="fsv-hero__meta-item">
                        <x-heroicon-o-user-group class="fsv-hero__meta-icon" />
                        <span>{{ $enrolledCount }} طالب مسجل</span>
                    </div>
                </div>
            </div>
        </section>

        {{-- Students table --}}
        {{ $this->table }}
    </div>

    @push('styles')
    <style>
        .figma-enroll-view .fsv-hero {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
            padding: 25px;
            background: #ffffff;
            border: 1px solid #e4e4e7;
            border-radius: 12px;
        }

        /* --- Actions / print menu --- */
        .figma-enroll-view .fsv-hero__actions {
            display: flex;
            align-items: center;
            gap: 8px;
            order: 2;
        }
        .figma-enroll-view .fsv-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 17px;
            border-radius: 8px;
            font-size: 14px;
            line-height: 20px;
            font-weight: 500;
            cursor: pointer;
            transition: opacity .15s ease, background .15s ease, border-color .15s ease;
            white-space: nowrap;
        }
        .figma-enroll-view .fsv-btn__icon { width: 16px; height: 16px; }
        .figma-enroll-view .fsv-btn--secondary {
            background: #ffffff;
            color: #09090b;
            border: 1px solid #e4e4e7;
        }
        .figma-enroll-view .fsv-btn--secondary:hover { background: #f4f4f5; }
        .dark .figma-enroll-view .fsv-btn--secondary {
            background: rgb(39 39 42);
            color: #fafafa;
            border-color: rgb(63 63 70);
        }
        .dark .figma-enroll-view .fsv-btn--secondary:hover { background: rgb(63 63 70); }
        .figma-enroll-view .fsv-print-menu__panel {
            position: absolute;
            top: calc(100% + 6px);
            inset-inline-end: 0;
            min-width: 210px;
            background: #fff;
            border: 1px solid #e4e4e7;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0,0,0,.08);
            padding: 6px;
            z-index: 50;
        }
        .dark .figma-enroll-view .fsv-print-menu__panel {
            background: rgb(39 39 42);
            border-color: rgb(63 63 70);
            box-shadow: 0 8px 24px rgba(0,0,0,.4);
        }
        .figma-enroll-view .fsv-print-menu__item {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 9px 12px;
            font-size: 14px;
            font-weight: 500;
            text-align: right;
            color: #18181b;
            background: transparent;
            border: 0;
            border-radius: 6px;
            cursor: pointer;
            transition: background 120ms;
        }
        .figma-enroll-view .fsv-print-menu__item:hover { background: #f4f4f5; }
        .dark .figma-enroll-view .fsv-print-menu__item { color: #fafafa; }
        .dark .figma-enroll-view .fsv-print-menu__item:hover { background: rgb(63 63 70); }
        .figma-enroll-view .fsv-print-menu__icon { width: 16px; height: 16px; flex-shrink: 0; color: #71717a; }
        .dark .figma-enroll-view .fsv-print-menu__icon { color: #a1a1aa; }
        [x-cloak] { display: none !important; }
        .dark .figma-enroll-view .fsv-hero {
            background: rgb(24 24 27);
            border-color: rgb(63 63 70);
        }

        .figma-enroll-view .fsv-hero__identity {
            display: flex;
            flex-direction: column;
            gap: 7.5px;
            flex: 1;
            min-width: 0;
            order: 1;
            align-items: flex-start;
            text-align: right;
        }
        .figma-enroll-view .fsv-hero__name-row {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .figma-enroll-view .fsv-hero__name-row .fsv-hero__name { margin-inline-end: 12px; }

        .figma-enroll-view .fsv-hero__name {
            font-size: 24px;
            line-height: 32px;
            font-weight: 700;
            color: #09090b;
            margin: 0;
        }
        .dark .figma-enroll-view .fsv-hero__name { color: #fafafa; }

        .figma-enroll-view .fsv-hero__enrollment {
            display: inline-flex;
            align-items: baseline;
            gap: 8px;
            margin-top: 2px;
        }
        .figma-enroll-view .fsv-hero__enrollment-label {
            font-size: 14px;
            color: #09090b;
            font-weight: 500;
        }
        .dark .figma-enroll-view .fsv-hero__enrollment-label { color: #fafafa; }
        .figma-enroll-view .fsv-hero__enrollment-value {
            font-size: 22px;
            font-weight: 700;
            color: #09090b;
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.01em;
        }
        .dark .figma-enroll-view .fsv-hero__enrollment-value { color: #fafafa; }

        /* Muted pills */
        .figma-enroll-view .fsv-status-pill {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border-radius: 4px;
            font-size: 12px;
            line-height: 16px;
            font-weight: 700;
        }
        .figma-enroll-view .fsv-status-pill--success { background: #f0fdf4; border: 1px solid #dcfce7; color: #166534; }
        .figma-enroll-view .fsv-status-pill--danger  { background: #fef2f2; border: 1px solid #fee2e2; color: #991b1b; }
        .figma-enroll-view .fsv-status-pill--warning { background: #fffbeb; border: 1px solid #fef3c7; color: #92400e; }
        .figma-enroll-view .fsv-status-pill--info    { background: #eff6ff; border: 1px solid #dbeafe; color: #1e40af; }
        .figma-enroll-view .fsv-status-pill--gray    { background: #fafafa; border: 1px solid #e4e4e7; color: #52525b; }
        .dark .figma-enroll-view .fsv-status-pill--success { background: rgba(34, 197, 94, 0.08); border-color: rgba(34, 197, 94, 0.18); color: #86efac; }
        .dark .figma-enroll-view .fsv-status-pill--danger  { background: rgba(239, 68, 68, 0.08); border-color: rgba(239, 68, 68, 0.18); color: #fca5a5; }
        .dark .figma-enroll-view .fsv-status-pill--warning { background: rgba(245, 158, 11, 0.08); border-color: rgba(245, 158, 11, 0.18); color: #fcd34d; }
        .dark .figma-enroll-view .fsv-status-pill--info    { background: rgba(59, 130, 246, 0.08); border-color: rgba(59, 130, 246, 0.18); color: #93c5fd; }

        .figma-enroll-view .fsv-role-pill {
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
        .dark .figma-enroll-view .fsv-role-pill {
            background: rgba(244, 244, 245, 0.06);
            border-color: rgb(63 63 70);
            color: #d4d4d8;
        }

        .figma-enroll-view .fsv-hero__meta {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .figma-enroll-view .fsv-hero__meta-item {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 14px;
            line-height: 20px;
            color: #52525b; /* H-1: bumped from #71717a for WCAG AA (light mode) */
        }
        .figma-enroll-view .fsv-hero__meta-icon { width: 14px; height: 14px; color: #52525b; }
        .dark .figma-enroll-view .fsv-hero__meta-item,
        .dark .figma-enroll-view .fsv-hero__meta-icon { color: #71717a; }

        /* Table wrapped as a card */
        .figma-enroll-view .fi-ta-ctn {
            background: #ffffff;
            border: 1px solid #e4e4e7;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: none;
        }
        .dark .figma-enroll-view .fi-ta-ctn {
            background: rgb(24 24 27);
            border-color: rgb(63 63 70);
        }
        .figma-enroll-view .fi-ta-header {
            background: rgba(244, 244, 245, 0.5);
            border-bottom: 1px solid #e4e4e7;
            padding: 12px 20px 13px;
        }
        .dark .figma-enroll-view .fi-ta-header {
            background: rgba(63, 63, 70, 0.3);
            border-bottom-color: rgb(63 63 70);
        }
    </style>
    @endpush
</x-filament-panels::page>
