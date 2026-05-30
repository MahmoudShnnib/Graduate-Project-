<x-filament-panels::page>
    {{-- ============================================================
         /admin/students/{id} — redesigned to match Figma file
         u0QcJArEjXhhdXM6EGVZ7S → PvkSVB2Wa892WD742MiFyv (node 1:2)
         Backup of the previous layout: _backups/student-view-2026-05-11/
       ============================================================ --}}

    @php
        /** @var \App\Models\Student $student */
        $student = $this->record;
        $studentName = $student->user?->name ?? '—';
        $statusLabel = $student->getDisplayStatusLabel();
        $statusColor = $student->getDisplayStatusColor();
        $editUrl = \App\Filament\Resources\Students\StudentResource::getUrl('edit', ['record' => $student]);
    @endphp

    <div class="figma-student-view space-y-6" dir="rtl">

        {{-- HERO CARD --}}
        <section class="fsv-hero">
            <div class="fsv-hero__actions">
                <a href="{{ $editUrl }}" class="fsv-btn fsv-btn--primary">
                    <span>تعديل</span>
                    <x-heroicon-o-pencil-square class="fsv-btn__icon" />
                </a>

                @if(in_array(auth()->user()?->role, ['super_admin', 'secretary'], true))
                    <div class="fsv-print-menu" x-data="{ open: false }" @click.outside="open = false" style="position: relative;">
                        <button type="button" @click="open = ! open" class="fsv-btn fsv-btn--secondary">
                            <span>طباعة</span>
                            <x-heroicon-o-printer class="fsv-btn__icon" />
                        </button>

                        <div x-show="open" x-cloak x-transition class="fsv-print-menu__panel">
                            <a href="{{ route('print.student-letter', ['student' => $student]) }}" target="_blank" rel="noopener" class="fsv-print-menu__item"
                                    @click="open = false">
                                <x-heroicon-o-document-text class="fsv-print-menu__icon" />
                                <span>تعريف الطالب</span>
                            </a>
                            <a href="{{ route('print.provisional-grades', ['student' => $student]) }}" target="_blank" rel="noopener" class="fsv-print-menu__item"
                                    @click="open = false">
                                <x-heroicon-o-academic-cap class="fsv-print-menu__icon" />
                                <span>كشف درجات مؤقت</span>
                            </a>
                        </div>
                    </div>
                @endif
            </div>

            @php
                $heroGpa = $student->gpa !== null ? (float) $student->gpa : null;
                $heroGpaTier = match (true) {
                    $heroGpa === null => null,
                    $heroGpa >= 3.5 => ['label' => 'ممتاز', 'class' => 'success'],
                    $heroGpa >= 3.0 => ['label' => 'جيد جداً', 'class' => 'success'],
                    $heroGpa >= 2.5 => ['label' => 'جيد', 'class' => 'info'],
                    $heroGpa >= 2.0 => ['label' => 'مقبول', 'class' => 'warning'],
                    default => ['label' => 'ضعيف', 'class' => 'danger'],
                };
            @endphp

            <div class="fsv-hero__identity">
                <div class="fsv-hero__name-row">
                    <h1 class="fsv-hero__name">{{ $studentName }}</h1>

                    <span @class([
                        'fsv-status-pill',
                        'fsv-status-pill--success' => $statusColor === 'success',
                        'fsv-status-pill--danger' => $statusColor === 'danger',
                        'fsv-status-pill--warning' => $statusColor === 'warning',
                        'fsv-status-pill--info' => $statusColor === 'info',
                        'fsv-status-pill--gray' => ! in_array($statusColor, ['success', 'danger', 'warning', 'info']),
                    ])>{{ $statusLabel }}</span>

                    @if ($heroGpaTier)
                        <span class="fsv-gpa-chip fsv-gpa-chip--{{ $heroGpaTier['class'] }}" title="المعدل التراكمي">
                            <span class="fsv-gpa-chip__tier">{{ $heroGpaTier['label'] }}</span>
                            <span class="fsv-gpa-chip__divider"></span>
                            <span class="fsv-gpa-chip__value">{{ number_format($heroGpa, 2) }}</span>
                            <span class="fsv-gpa-chip__label">المعدل</span>
                        </span>
                    @endif
                </div>

                @if ($student->enrollment_no)
                    <div class="fsv-hero__enrollment">
                        <span class="fsv-hero__enrollment-label">رقم القيد:</span>
                        <span class="fsv-hero__enrollment-value">{{ $student->enrollment_no }}</span>
                    </div>
                @endif

                <div class="fsv-hero__meta">
                    @if ($student->residence)
                        <div class="fsv-hero__meta-item">
                            <x-heroicon-o-map-pin class="fsv-hero__meta-icon" />
                            <span>{{ $student->residence }}</span>
                        </div>
                    @endif

                    @if ($student->national_id)
                        <div class="fsv-hero__meta-item">
                            <x-heroicon-o-hashtag class="fsv-hero__meta-icon" />
                            <span class="fsv-hero__meta-label">الرقم الوطني:</span>
                            <span class="fsv-hero__meta-value">{{ $student->national_id }}</span>
                        </div>
                    @endif
                </div>
            </div>

        </section>

        {{-- INFOLIST: rendered with restyled section cards (see CSS below) --}}
        @if($this->hasInfolist())
            {{ $this->infolist }}
        @endif

        {{-- Enrollment history across all semesters --}}
        <x-filament::section heading="المقررات المسجلة" collapsible>
            {{ $this->table }}
        </x-filament::section>

        {{-- Activity Timeline --}}
        @php
            $timeline = $this->getActivityTimeline();
            $timelineLimit = 20;
            $hasMore = $timeline->count() > $timelineLimit;
            $visibleTimeline = $this->showAllActivity ? $timeline : $timeline->take($timelineLimit);
        @endphp
        <x-filament::section collapsible collapsed>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <span>سجل النشاط</span>
                    @if($timeline->isNotEmpty())
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-400">{{ $timeline->count() }}</span>
                    @endif
                </div>
            </x-slot>

            @if($timeline->isEmpty())
                <div class="text-center py-8">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 dark:bg-white/10 mx-auto mb-3">
                        <x-heroicon-o-clock style="width: 1.25rem; height: 1.25rem;" class="text-gray-400 dark:text-gray-500" />
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">لا توجد أنشطة مسجلة لهذا الطالب.</p>
                </div>
            @else
                <div class="relative space-y-0">
                    {{-- Vertical line --}}
                    <div class="absolute start-5 top-3 bottom-3 w-px bg-gray-200 dark:bg-white/10"></div>

                    @foreach($visibleTimeline as $entry)
                        <div class="relative flex items-start gap-4 py-3">
                            {{-- Icon dot --}}
                            <div @class([
                                'relative z-10 flex h-10 w-10 shrink-0 items-center justify-center rounded-full',
                                'bg-info-100 dark:bg-info-500/20' => $entry['color'] === 'info',
                                'bg-warning-100 dark:bg-warning-500/20' => $entry['color'] === 'warning',
                                'bg-danger-100 dark:bg-danger-500/20' => $entry['color'] === 'danger',
                                'bg-primary-100 dark:bg-primary-500/20' => $entry['color'] === 'primary',
                                'bg-success-100 dark:bg-success-500/20' => $entry['color'] === 'success',
                                'bg-gray-100 dark:bg-white/10' => $entry['color'] === 'gray',
                            ])>
                                <x-filament::icon
                                    :icon="$entry['icon']"
                                    @class([
                                        'h-5 w-5',
                                        'text-info-600 dark:text-info-400' => $entry['color'] === 'info',
                                        'text-warning-600 dark:text-warning-400' => $entry['color'] === 'warning',
                                        'text-danger-600 dark:text-danger-400' => $entry['color'] === 'danger',
                                        'text-primary-600 dark:text-primary-400' => $entry['color'] === 'primary',
                                        'text-success-600 dark:text-success-400' => $entry['color'] === 'success',
                                        'text-gray-400 dark:text-gray-500' => $entry['color'] === 'gray',
                                    ])
                                />
                            </div>

                            {{-- Content --}}
                            <div class="flex-1 min-w-0 pt-1.5">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $entry['title'] }}</p>
                                <div class="flex items-center gap-2 mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                    <span>{{ $entry['date']->format('Y/m/d — H:i') }}</span>
                                    @if($entry['detail'])
                                        <span class="text-gray-300 dark:text-gray-600">&middot;</span>
                                        <span>{{ $entry['detail'] }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($hasMore)
                    <div class="mt-4 text-center">
                        <button
                            wire:click="toggleShowAllActivity"
                            class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline"
                        >
                            {{ $this->showAllActivity ? 'عرض الأحدث فقط' : 'عرض الكل ('.$timeline->count().')' }}
                        </button>
                    </div>
                @endif
            @endif
        </x-filament::section>
    </div>

    {{-- ============================================================
         Scoped styles — Figma look applied to Filament primitives.
         All selectors live under .figma-student-view so the restyle
         is page-local.
       ============================================================ --}}
    @push('styles')
    <style>
        /* ---------- Hero card ---------- */
        .figma-student-view .fsv-hero {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
            padding: 25px;
            background: #ffffff;
            border: 1px solid #e4e4e7;
            border-radius: 12px;
        }
        .dark .figma-student-view .fsv-hero {
            background: rgb(24 24 27);
            border-color: rgb(63 63 70);
        }

        .figma-student-view .fsv-hero__actions {
            display: flex;
            align-items: center;
            gap: 8px;
            order: 2; /* visually-left in RTL */
        }

        .figma-student-view .fsv-btn {
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
        .figma-student-view .fsv-btn__icon { width: 16px; height: 16px; }

        .figma-student-view .fsv-btn--primary {
            background: #1F4E79;
            color: #ffffff;
            border: 1px solid #1F4E79;
            box-shadow: 0 1px 2px rgba(31, 78, 121, 0.12);
        }
        .figma-student-view .fsv-btn--primary:hover {
            background: #16395a;
            border-color: #16395a;
            color: #ffffff;
        }
        .dark .figma-student-view .fsv-btn--primary {
            background: #2f6aa8;
            border-color: #2f6aa8;
            color: #ffffff;
        }
        .dark .figma-student-view .fsv-btn--primary:hover { background: #1F4E79; border-color: #1F4E79; }

        .figma-student-view .fsv-btn--full { width: 100%; justify-content: center; }

        .figma-student-view .fsv-btn--secondary {
            background: #ffffff;
            color: #09090b;
            border: 1px solid #e4e4e7;
        }
        .figma-student-view .fsv-btn--secondary:hover { background: #f4f4f5; }
        .dark .figma-student-view .fsv-btn--secondary {
            background: rgb(39 39 42);
            color: #fafafa;
            border-color: rgb(63 63 70);
        }
        .dark .figma-student-view .fsv-btn--secondary:hover { background: rgb(63 63 70); }

        .figma-student-view .fsv-print-menu__panel {
            position: absolute;
            top: calc(100% + 6px);
            inset-inline-end: 0;
            min-width: 200px;
            background: #fff;
            border: 1px solid #e4e4e7;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            padding: 6px;
            z-index: 50;
        }
        .dark .figma-student-view .fsv-print-menu__panel {
            background: rgb(39 39 42);
            border-color: rgb(63 63 70);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
        }
        .figma-student-view .fsv-print-menu__item {
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
        .figma-student-view .fsv-print-menu__item:hover { background: #f4f4f5; }
        .dark .figma-student-view .fsv-print-menu__item {
            color: #fafafa;
        }
        .dark .figma-student-view .fsv-print-menu__item:hover { background: rgb(63 63 70); }
        .figma-student-view .fsv-print-menu__icon {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            color: #71717a;
        }
        .dark .figma-student-view .fsv-print-menu__icon { color: #a1a1aa; }
        [x-cloak] { display: none !important; }

        .figma-student-view .fsv-hero__identity {
            display: flex;
            flex-direction: column;
            gap: 7.5px;
            flex: 1;
            min-width: 0;
            order: 1;
            /* In RTL column flex, the cross-axis START is on the right,
               so flex-start anchors children to the right edge. */
            align-items: flex-start;
            text-align: right;
        }

        .figma-student-view .fsv-hero__name-row {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        /* Extra breathing room between the name and the chips cluster */
        .figma-student-view .fsv-hero__name-row .fsv-hero__name {
            margin-inline-end: 12px;
        }

        .figma-student-view .fsv-hero__enrollment {
            display: inline-flex;
            align-items: baseline;
            gap: 8px;
            margin-top: 2px;
        }
        .figma-student-view .fsv-hero__enrollment-label {
            font-size: 14px;
            color: #09090b;
            font-weight: 500;
        }
        .dark .figma-student-view .fsv-hero__enrollment-label { color: #fafafa; }
        .figma-student-view .fsv-hero__enrollment-value {
            font-size: 22px;
            font-weight: 700;
            color: #09090b;
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.01em;
        }
        .dark .figma-student-view .fsv-hero__enrollment-value { color: #fafafa; }

        .figma-student-view .fsv-hero__name {
            font-size: 24px;
            line-height: 32px;
            font-weight: 700;
            color: #09090b;
            margin: 0;
        }
        .dark .figma-student-view .fsv-hero__name { color: #fafafa; }

        .figma-student-view .fsv-status-pill {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border-radius: 4px;
            font-size: 12px;
            line-height: 16px;
            font-weight: 700;
        }
        .figma-student-view .fsv-status-pill--success { background: #f0fdf4; border: 1px solid #dcfce7; color: #166534; }
        .figma-student-view .fsv-status-pill--danger  { background: #fef2f2; border: 1px solid #fee2e2; color: #991b1b; }
        .figma-student-view .fsv-status-pill--warning { background: #fffbeb; border: 1px solid #fef3c7; color: #92400e; }
        .figma-student-view .fsv-status-pill--info    { background: #eff6ff; border: 1px solid #dbeafe; color: #1e40af; }
        .figma-student-view .fsv-status-pill--gray    { background: #fafafa; border: 1px solid #e4e4e7; color: #52525b; }
        .dark .figma-student-view .fsv-status-pill--success { background: rgba(34, 197, 94, 0.08); border-color: rgba(34, 197, 94, 0.18); color: #86efac; }
        .dark .figma-student-view .fsv-status-pill--danger  { background: rgba(239, 68, 68, 0.08); border-color: rgba(239, 68, 68, 0.18); color: #fca5a5; }
        .dark .figma-student-view .fsv-status-pill--warning { background: rgba(245, 158, 11, 0.08); border-color: rgba(245, 158, 11, 0.18); color: #fcd34d; }
        .dark .figma-student-view .fsv-status-pill--info    { background: rgba(59, 130, 246, 0.08); border-color: rgba(59, 130, 246, 0.18); color: #93c5fd; }

        /* GPA chip next to the status pill in the hero name-row */
        .figma-student-view .fsv-gpa-chip {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 5px 12px 5px 5px;
            border-radius: 8px;
            font-size: 14px;
            line-height: 20px;
            font-weight: 600;
            background: #ffffff;
            border: 1px solid #e4e4e7;
        }
        .dark .figma-student-view .fsv-gpa-chip {
            background: rgb(39 39 42);
            border-color: rgb(63 63 70);
        }
        .figma-student-view .fsv-gpa-chip__label {
            color: #52525b; /* H-2: bumped from #71717a for WCAG AA (light mode) */
            font-weight: 500;
            font-size: 13px;
        }
        .dark .figma-student-view .fsv-gpa-chip__label { color: #71717a; }
        .figma-student-view .fsv-gpa-chip__value {
            color: #09090b;
            font-weight: 700;
            font-size: 16px;
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.01em;
        }
        .dark .figma-student-view .fsv-gpa-chip__value { color: #fafafa; }
        .figma-student-view .fsv-gpa-chip__divider {
            width: 1px;
            height: 16px;
            background: #e4e4e7;
        }
        .dark .figma-student-view .fsv-gpa-chip__divider { background: rgb(63 63 70); }
        .figma-student-view .fsv-gpa-chip__tier {
            display: inline-flex;
            align-items: center;
            padding: 2px 9px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 12px;
            line-height: 16px;
            border: 1px solid transparent;
        }
        .figma-student-view .fsv-gpa-chip--success .fsv-gpa-chip__tier { background: #f0fdf4; border-color: #dcfce7; color: #166534; }
        .figma-student-view .fsv-gpa-chip--info    .fsv-gpa-chip__tier { background: #eff6ff; border-color: #dbeafe; color: #1e40af; }
        .figma-student-view .fsv-gpa-chip--warning .fsv-gpa-chip__tier { background: #fffbeb; border-color: #fef3c7; color: #92400e; }
        .figma-student-view .fsv-gpa-chip--danger  .fsv-gpa-chip__tier { background: #fef2f2; border-color: #fee2e2; color: #991b1b; }
        .dark .figma-student-view .fsv-gpa-chip--success .fsv-gpa-chip__tier { background: rgba(34, 197, 94, 0.08); border-color: rgba(34, 197, 94, 0.18); color: #86efac; }
        .dark .figma-student-view .fsv-gpa-chip--info    .fsv-gpa-chip__tier { background: rgba(59, 130, 246, 0.08); border-color: rgba(59, 130, 246, 0.18); color: #93c5fd; }
        .dark .figma-student-view .fsv-gpa-chip--warning .fsv-gpa-chip__tier { background: rgba(245, 158, 11, 0.08); border-color: rgba(245, 158, 11, 0.18); color: #fcd34d; }
        .dark .figma-student-view .fsv-gpa-chip--danger  .fsv-gpa-chip__tier { background: rgba(239, 68, 68, 0.08); border-color: rgba(239, 68, 68, 0.18); color: #fca5a5; }

        .figma-student-view .fsv-hero__meta {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .figma-student-view .fsv-hero__meta-item {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 14px;
            line-height: 20px;
            color: #52525b; /* H-1: bumped from #71717a for WCAG AA (light mode) */
        }
        .figma-student-view .fsv-hero__meta-value { color: #52525b; }
        .figma-student-view .fsv-hero__meta-label { color: #52525b; }
        .figma-student-view .fsv-hero__meta-icon { width: 14px; height: 14px; color: #52525b; }
        .dark .figma-student-view .fsv-hero__meta-item,
        .dark .figma-student-view .fsv-hero__meta-value,
        .dark .figma-student-view .fsv-hero__meta-label,
        .dark .figma-student-view .fsv-hero__meta-icon { color: #71717a; }

        /* ---------- Filament Section cards within this page ---------- */
        .figma-student-view .fi-section {
            background: #ffffff;
            border: 1px solid #e4e4e7;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: none;
        }
        .dark .figma-student-view .fi-section {
            background: rgb(24 24 27);
            border-color: rgb(63 63 70);
        }

        /* Section header */
        .figma-student-view .fi-section-header {
            background: rgba(244, 244, 245, 0.5);
            border-bottom: 1px solid #e4e4e7;
            padding: 12px 20px 13px;
        }
        .dark .figma-student-view .fi-section-header {
            background: rgba(63, 63, 70, 0.3);
            border-bottom-color: rgb(63 63 70);
        }
        .figma-student-view .fi-section-header-heading {
            font-family: inherit;
            font-size: 16px;
            line-height: 24px;
            font-weight: 700;
            color: #09090b;
            text-align: right;
        }
        .dark .figma-student-view .fi-section-header-heading { color: #fafafa; }

        /* Section body */
        .figma-student-view .fi-section-content,
        .figma-student-view .fi-section-content-ctn {
            padding: 20px;
        }

        /* Row separators between Filament infolist entries */
        .figma-student-view .fi-section-content .fi-in-entry-wrp,
        .figma-student-view .fi-section-content .fi-in-text-entry {
            padding-top: 8px;
            padding-bottom: 9px;
            border-bottom: 1px solid #e4e4e7;
        }
        .figma-student-view .fi-section-content .fi-in-entry-wrp:last-child,
        .figma-student-view .fi-section-content .fi-in-text-entry:last-child {
            border-bottom: 0;
            padding-bottom: 8px;
        }
        .dark .figma-student-view .fi-section-content .fi-in-entry-wrp,
        .dark .figma-student-view .fi-section-content .fi-in-text-entry {
            border-bottom-color: rgb(63 63 70);
        }

        /* Inline labels (label-right, value-left in RTL) */
        .figma-student-view .fi-in-entry-label {
            font-size: 14px;
            line-height: 20px;
            color: #71717a;
            font-weight: 400;
        }
        .figma-student-view .fi-in-entry .fi-in-text {
            font-size: 14px;
            line-height: 20px;
            color: #09090b;
        }
        .dark .figma-student-view .fi-in-entry .fi-in-text { color: #fafafa; }

        /* Muted small icons next to values */
        .figma-student-view .fi-in-entry .fi-icon { color: #71717a; }

        /* Transfer-student callout inside the Study card */
        .figma-student-view .fsv-transfer-callout {
            background: #fef2f2 !important;
            border: 1px solid #ffe2e2 !important;
            border-radius: 8px;
            padding: 13px !important;
            margin-top: 16px;
        }
        .figma-student-view .fsv-transfer-callout .fi-in-text,
        .figma-student-view .fsv-transfer-callout .fi-in-text * { color: #c10007 !important; }
        .figma-student-view .fsv-transfer-callout .fi-icon { color: #c10007 !important; }
        .dark .figma-student-view .fsv-transfer-callout {
            background: rgba(239, 68, 68, 0.12) !important;
            border-color: rgba(239, 68, 68, 0.3) !important;
        }

        /* ---------- Degree document panel (rendered inside a Filament Section) ---------- */
        .figma-student-view .fsv-degree-doc {
            display: flex;
            flex-direction: column;
            gap: 12px;
            min-width: 0;
        }
        .figma-student-view .fsv-degree-doc__empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 48px 24px;
            background: #fafafa;
            border: 1px dashed #e4e4e7;
            border-radius: 12px;
            color: #71717a;
            font-size: 14px;
        }
        .dark .figma-student-view .fsv-degree-doc__empty {
            background: rgba(244, 244, 245, 0.04);
            border-color: rgb(63 63 70);
        }
        .figma-student-view .fsv-degree-doc__empty-icon { width: 32px; height: 32px; color: #a1a1aa; }

        /* PDF link card */
        .figma-student-view .fsv-degree-doc__pdf {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background: rgba(31, 78, 121, 0.04);
            border: 1px solid rgba(31, 78, 121, 0.15);
            border-radius: 12px;
            color: #09090b;
            text-decoration: none;
            transition: background .15s ease, border-color .15s ease;
        }
        .figma-student-view .fsv-degree-doc__pdf:hover {
            background: rgba(31, 78, 121, 0.08);
            border-color: rgba(31, 78, 121, 0.25);
            color: #09090b;
        }
        .dark .figma-student-view .fsv-degree-doc__pdf {
            background: rgba(47, 106, 168, 0.10);
            border-color: rgba(47, 106, 168, 0.30);
            color: #fafafa;
        }
        .dark .figma-student-view .fsv-degree-doc__pdf:hover {
            background: rgba(47, 106, 168, 0.16);
            color: #fafafa;
        }
        .figma-student-view .fsv-degree-doc__pdf-icon {
            width: 36px;
            height: 36px;
            color: #1F4E79;
            flex-shrink: 0;
        }
        .figma-student-view .fsv-degree-doc__pdf-meta {
            display: flex;
            flex-direction: column;
            gap: 2px;
            flex: 1;
            min-width: 0;
            text-align: right;
        }
        .figma-student-view .fsv-degree-doc__pdf-title {
            font-size: 14px;
            line-height: 20px;
            font-weight: 600;
            color: #09090b;
        }
        .dark .figma-student-view .fsv-degree-doc__pdf-title { color: #fafafa; }
        .figma-student-view .fsv-degree-doc__pdf-sub {
            font-size: 12px;
            line-height: 16px;
            color: #71717a;
        }
        .figma-student-view .fsv-degree-doc__pdf-arrow { width: 18px; height: 18px; color: #71717a; flex-shrink: 0; }

        /* Image thumbnail */
        .figma-student-view .fsv-degree-doc__image-wrap {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .figma-student-view .fsv-degree-doc__image-btn {
            position: relative;
            display: block;
            width: 100%;
            padding: 0;
            border: 1px solid #e4e4e7;
            background: #fafafa;
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            transition: border-color .15s ease, transform .15s ease;
        }
        .figma-student-view .fsv-degree-doc__image-btn:hover {
            border-color: #1F4E79;
        }
        .figma-student-view .fsv-degree-doc__image-btn:hover .fsv-degree-doc__image-overlay { opacity: 1; }
        .dark .figma-student-view .fsv-degree-doc__image-btn { background: rgb(39 39 42); border-color: rgb(63 63 70); }

        .figma-student-view .fsv-degree-doc__image {
            display: block;
            width: 100%;
            height: 140px;
            object-fit: cover;
            object-position: center;
        }
        .figma-student-view .fsv-degree-doc__image-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(31, 78, 121, 0.5);
            opacity: 0;
            transition: opacity .15s ease;
        }
        .figma-student-view .fsv-degree-doc__image-overlay-icon {
            width: 40px;
            height: 40px;
            color: #ffffff;
        }

        /* Full-size preview modal (Alpine) */
        .figma-student-view .fsv-preview-modal {
            position: fixed;
            inset: 0;
            z-index: 60;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .figma-student-view .fsv-preview-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(9, 9, 11, 0.75);
            backdrop-filter: blur(2px);
        }
        .figma-student-view .fsv-preview-modal__panel {
            position: relative;
            max-width: 90vw;
            max-height: 90vh;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
        }
        .dark .figma-student-view .fsv-preview-modal__panel { background: rgb(24 24 27); }
        .figma-student-view .fsv-preview-modal__image {
            display: block;
            max-width: 90vw;
            max-height: 90vh;
            width: auto;
            height: auto;
            object-fit: contain;
        }
        .figma-student-view .fsv-preview-modal__close {
            position: absolute;
            top: 12px;
            left: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.95);
            color: #09090b;
            cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.15);
            transition: background .15s ease;
        }
        .figma-student-view .fsv-preview-modal__close:hover { background: #ffffff; }
        .figma-student-view .fsv-preview-modal__close-icon { width: 20px; height: 20px; }

        [x-cloak] { display: none !important; }

        /* ---------- Print styles ---------- */
        @media print {
            .fi-sidebar,
            .fi-topbar,
            .fi-header,
            .fi-breadcrumbs,
            .figma-student-view .fsv-hero__actions,
            .figma-student-view .fsv-preview-modal,
            .figma-student-view .fsv-degree-doc__image-btn .fsv-degree-doc__image-overlay {
                display: none !important;
            }
            .figma-student-view { padding: 0 !important; }
            .figma-student-view .fi-section,
            .figma-student-view .fsv-hero {
                box-shadow: none !important;
                page-break-inside: avoid;
            }
        }
    </style>
    @endpush
</x-filament-panels::page>
