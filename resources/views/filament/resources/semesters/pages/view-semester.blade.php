<x-filament-panels::page>
    {{-- ============================================================
         /admin/semesters/{id} — Figma view-page design language
         Mirrors student/user/application views.
         Workflow actions (publish midterms/finals, archive, edit) stay
         in the page header — they're driven by Filament Action objects.
         Backup: _backups/semester-view-2026-05-13/
       ============================================================ --}}

    @php
        /** @var \App\Models\Semester $semester */
        $semester = $this->record;
        $semesterName = $semester->name ?? '—';
        $isArchived = $semester->isArchived();
        $statusLabel = $isArchived ? 'مؤرشف' : 'نشط';
        $statusColor = $isArchived ? 'gray' : 'success';
        $seasonLabel = $semester->season?->label();
        $offeringsCount = $semester->courseOfferings()->count();
        $isAddDropOpen = $semester->isAddDropOpen();
    @endphp

    <div class="figma-semester-view space-y-6" dir="rtl">

        {{-- HERO CARD --}}
        <section class="fsv-hero">
            <div class="fsv-hero__actions">
                <button type="button" onclick="window.print(); return false;" class="fsv-btn fsv-btn--secondary">
                    <span>طباعة</span>
                    <x-heroicon-o-printer class="fsv-btn__icon" />
                </button>
            </div>

            <div class="fsv-hero__identity">
                <div class="fsv-hero__name-row">
                    <h1 class="fsv-hero__name">{{ $semesterName }}</h1>

                    <span @class([
                        'fsv-status-pill',
                        "fsv-status-pill--{$statusColor}",
                    ])>{{ $statusLabel }}</span>

                    @if ($isAddDropOpen)
                        <span class="fsv-role-pill fsv-role-pill--info">إضافة وحذف مفتوح</span>
                    @endif
                </div>

                @if ($seasonLabel)
                    <div class="fsv-hero__enrollment">
                        <span class="fsv-hero__enrollment-label">الموسم:</span>
                        <span class="fsv-hero__enrollment-value">{{ $seasonLabel }} {{ $semester->year }}</span>
                    </div>
                @endif

                <div class="fsv-hero__meta">
                    @if ($semester->start_date && $semester->end_date)
                        <div class="fsv-hero__meta-item">
                            <x-heroicon-o-calendar-days class="fsv-hero__meta-icon" />
                            <span>{{ $semester->start_date->format('Y/m/d') }} → {{ $semester->end_date->format('Y/m/d') }}</span>
                        </div>
                    @endif

                    @if ($semester->add_drop_start && $semester->add_drop_end)
                        <div class="fsv-hero__meta-item">
                            <x-heroicon-o-clock class="fsv-hero__meta-icon" />
                            <span>الإضافة والحذف: {{ $semester->add_drop_start->format('Y/m/d') }} → {{ $semester->add_drop_end->format('Y/m/d') }}</span>
                        </div>
                    @endif

                    <div class="fsv-hero__meta-item">
                        <x-heroicon-o-book-open class="fsv-hero__meta-icon" />
                        <span>{{ $offeringsCount }} مقرر معروض</span>
                    </div>

                    @if ($isArchived)
                        <div class="fsv-hero__meta-item">
                            <x-heroicon-o-archive-box class="fsv-hero__meta-icon" />
                            <span>أُرشف في {{ $semester->archived_at->format('Y/m/d') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        {{-- CONTENT: infolist + relation managers (course offerings + decided applications) --}}
        {{ $this->content }}
    </div>

    @push('styles')
    <style>
        /* ---------- Hero card ---------- */
        .figma-semester-view .fsv-hero {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
            padding: 25px;
            background: #ffffff;
            border: 1px solid #e4e4e7;
            border-radius: 12px;
        }
        .dark .figma-semester-view .fsv-hero {
            background: rgb(24 24 27);
            border-color: rgb(63 63 70);
        }

        .figma-semester-view .fsv-hero__actions {
            display: flex;
            align-items: center;
            gap: 8px;
            order: 2;
        }

        .figma-semester-view .fsv-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 17px;
            border-radius: 8px;
            font-size: 14px;
            line-height: 20px;
            font-weight: 500;
            cursor: pointer;
            transition: background .15s ease, border-color .15s ease;
            white-space: nowrap;
        }
        .figma-semester-view .fsv-btn__icon { width: 16px; height: 16px; }
        .figma-semester-view .fsv-btn--secondary {
            background: #ffffff;
            color: #09090b;
            border: 1px solid #e4e4e7;
        }
        .figma-semester-view .fsv-btn--secondary:hover { background: #f4f4f5; }
        .dark .figma-semester-view .fsv-btn--secondary {
            background: rgb(39 39 42);
            color: #fafafa;
            border-color: rgb(63 63 70);
        }
        .dark .figma-semester-view .fsv-btn--secondary:hover { background: rgb(63 63 70); }

        .figma-semester-view .fsv-hero__identity {
            display: flex;
            flex-direction: column;
            gap: 7.5px;
            flex: 1;
            min-width: 0;
            order: 1;
            align-items: flex-start;
            text-align: right;
        }
        .figma-semester-view .fsv-hero__name-row {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .figma-semester-view .fsv-hero__name-row .fsv-hero__name { margin-inline-end: 12px; }

        .figma-semester-view .fsv-hero__name {
            font-size: 24px;
            line-height: 32px;
            font-weight: 700;
            color: #09090b;
            margin: 0;
        }
        .dark .figma-semester-view .fsv-hero__name { color: #fafafa; }

        .figma-semester-view .fsv-hero__enrollment {
            display: inline-flex;
            align-items: baseline;
            gap: 8px;
            margin-top: 2px;
        }
        .figma-semester-view .fsv-hero__enrollment-label {
            font-size: 14px;
            color: #09090b;
            font-weight: 500;
        }
        .dark .figma-semester-view .fsv-hero__enrollment-label { color: #fafafa; }
        .figma-semester-view .fsv-hero__enrollment-value {
            font-size: 20px;
            font-weight: 700;
            color: #09090b;
            letter-spacing: -0.01em;
        }
        .dark .figma-semester-view .fsv-hero__enrollment-value { color: #fafafa; }

        /* Muted pills */
        .figma-semester-view .fsv-status-pill {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border-radius: 4px;
            font-size: 12px;
            line-height: 16px;
            font-weight: 700;
        }
        .figma-semester-view .fsv-status-pill--success { background: #f0fdf4; border: 1px solid #dcfce7; color: #166534; }
        .figma-semester-view .fsv-status-pill--danger  { background: #fef2f2; border: 1px solid #fee2e2; color: #991b1b; }
        .figma-semester-view .fsv-status-pill--warning { background: #fffbeb; border: 1px solid #fef3c7; color: #92400e; }
        .figma-semester-view .fsv-status-pill--info    { background: #eff6ff; border: 1px solid #dbeafe; color: #1e40af; }
        .figma-semester-view .fsv-status-pill--gray    { background: #fafafa; border: 1px solid #e4e4e7; color: #52525b; }
        .dark .figma-semester-view .fsv-status-pill--success { background: rgba(34, 197, 94, 0.08); border-color: rgba(34, 197, 94, 0.18); color: #86efac; }
        .dark .figma-semester-view .fsv-status-pill--danger  { background: rgba(239, 68, 68, 0.08); border-color: rgba(239, 68, 68, 0.18); color: #fca5a5; }
        .dark .figma-semester-view .fsv-status-pill--warning { background: rgba(245, 158, 11, 0.08); border-color: rgba(245, 158, 11, 0.18); color: #fcd34d; }
        .dark .figma-semester-view .fsv-status-pill--info    { background: rgba(59, 130, 246, 0.08); border-color: rgba(59, 130, 246, 0.18); color: #93c5fd; }

        .figma-semester-view .fsv-role-pill {
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
        .figma-semester-view .fsv-role-pill--info { color: #1e40af; border-color: #dbeafe; background: #eff6ff; }
        .dark .figma-semester-view .fsv-role-pill {
            background: rgba(244, 244, 245, 0.06);
            border-color: rgb(63 63 70);
            color: #d4d4d8;
        }
        .dark .figma-semester-view .fsv-role-pill--info { color: #93c5fd; background: rgba(59, 130, 246, 0.08); border-color: rgba(59, 130, 246, 0.18); }

        .figma-semester-view .fsv-hero__meta {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .figma-semester-view .fsv-hero__meta-item {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 14px;
            line-height: 20px;
            color: #71717a;
        }
        .figma-semester-view .fsv-hero__meta-icon { width: 14px; height: 14px; color: #71717a; }

        /* ---------- Filament Section cards ---------- */
        .figma-semester-view .fi-section {
            background: #ffffff;
            border: 1px solid #e4e4e7;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: none;
        }
        .dark .figma-semester-view .fi-section {
            background: rgb(24 24 27);
            border-color: rgb(63 63 70);
        }
        .figma-semester-view .fi-section-header {
            background: rgba(244, 244, 245, 0.5);
            border-bottom: 1px solid #e4e4e7;
            padding: 12px 20px 13px;
        }
        .dark .figma-semester-view .fi-section-header {
            background: rgba(63, 63, 70, 0.3);
            border-bottom-color: rgb(63 63 70);
        }
        .figma-semester-view .fi-section-header-heading {
            font-family: inherit;
            font-size: 16px;
            line-height: 24px;
            font-weight: 700;
            color: #09090b;
            text-align: right;
        }
        .dark .figma-semester-view .fi-section-header-heading { color: #fafafa; }
        .figma-semester-view .fi-section-content,
        .figma-semester-view .fi-section-content-ctn { padding: 20px; }

        .figma-semester-view .fi-section-content .fi-in-entry-wrp,
        .figma-semester-view .fi-section-content .fi-in-text-entry {
            padding-top: 8px;
            padding-bottom: 9px;
            border-bottom: 1px solid #e4e4e7;
        }
        .figma-semester-view .fi-section-content .fi-in-entry-wrp:last-child,
        .figma-semester-view .fi-section-content .fi-in-text-entry:last-child {
            border-bottom: 0;
            padding-bottom: 8px;
        }
        .dark .figma-semester-view .fi-section-content .fi-in-entry-wrp,
        .dark .figma-semester-view .fi-section-content .fi-in-text-entry {
            border-bottom-color: rgb(63 63 70);
        }

        .figma-semester-view .fi-in-entry-label {
            font-size: 14px;
            line-height: 20px;
            color: #71717a;
            font-weight: 400;
        }
        .figma-semester-view .fi-in-entry .fi-in-text {
            font-size: 14px;
            line-height: 20px;
            color: #09090b;
        }
        .dark .figma-semester-view .fi-in-entry .fi-in-text { color: #fafafa; }
        .figma-semester-view .fi-in-entry .fi-icon { color: #71717a; }

        /* Relation manager cards */
        .figma-semester-view .fi-resource-relation-manager,
        .figma-semester-view .fi-ta-ctn {
            background: #ffffff;
            border: 1px solid #e4e4e7;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: none;
        }
        .dark .figma-semester-view .fi-resource-relation-manager,
        .dark .figma-semester-view .fi-ta-ctn {
            background: rgb(24 24 27);
            border-color: rgb(63 63 70);
        }
        .figma-semester-view .fi-ta-header {
            background: rgba(244, 244, 245, 0.5);
            border-bottom: 1px solid #e4e4e7;
            padding: 12px 20px 13px;
        }
        .dark .figma-semester-view .fi-ta-header {
            background: rgba(63, 63, 70, 0.3);
            border-bottom-color: rgb(63 63 70);
        }
        .figma-semester-view .fi-ta-header-heading {
            font-size: 16px;
            line-height: 24px;
            font-weight: 700;
        }

        @media print {
            .fi-sidebar,
            .fi-topbar,
            .fi-header,
            .fi-breadcrumbs,
            .figma-semester-view .fsv-hero__actions { display: none !important; }
            .figma-semester-view { padding: 0 !important; }
            .figma-semester-view .fi-section,
            .figma-semester-view .fsv-hero {
                box-shadow: none !important;
                page-break-inside: avoid;
            }
        }
    </style>
    @endpush
</x-filament-panels::page>
