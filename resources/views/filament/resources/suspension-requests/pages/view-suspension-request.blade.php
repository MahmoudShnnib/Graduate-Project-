<x-filament-panels::page>
    {{-- ============================================================
         /admin/suspension-requests/{id} — Figma view-page design language
         Mirrors /admin/applications/{id} and /admin/students/{id}.
         Backup: _backups/suspension-request-view-2026-05-14/
       ============================================================ --}}

    @php
        /** @var \App\Models\SuspensionRequest $request */
        $request = $this->record;
        $studentName = $request->student?->user?->name ?? '—';
        $enrollmentNo = $request->student?->enrollment_no;
        $semesterName = $request->semester?->name;
        $specializationName = $request->student?->specialization?->name;
        $statusEnum = $request->status;
        $statusLabel = $statusEnum?->label() ?? '—';
        $statusColor = match ($statusEnum) {
            \App\Enums\SuspensionStatus::Pending => 'warning',
            \App\Enums\SuspensionStatus::Approved => 'success',
            \App\Enums\SuspensionStatus::Rejected => 'danger',
            default => 'gray',
        };
    @endphp

    <div class="figma-suspension-view space-y-6" dir="rtl">

        {{-- HERO CARD --}}
        <section class="fsv-hero">
            <div class="fsv-hero__identity">
                <div class="fsv-hero__name-row">
                    <h1 class="fsv-hero__name">{{ $studentName }}</h1>

                    <span @class([
                        'fsv-status-pill',
                        "fsv-status-pill--{$statusColor}",
                    ])>{{ $statusLabel }}</span>

                    @if ($semesterName)
                        <span class="fsv-role-pill fsv-role-pill--info">{{ $semesterName }}</span>
                    @endif
                </div>

                @if ($enrollmentNo)
                    <div class="fsv-hero__enrollment">
                        <span class="fsv-hero__enrollment-label">رقم القيد:</span>
                        <span class="fsv-hero__enrollment-value">{{ $enrollmentNo }}</span>
                    </div>
                @endif

                <div class="fsv-hero__meta">
                    @if ($specializationName)
                        <div class="fsv-hero__meta-item">
                            <x-heroicon-o-academic-cap class="fsv-hero__meta-icon" />
                            <span>{{ $specializationName }}</span>
                        </div>
                    @endif

                    @if ($request->created_at)
                        <div class="fsv-hero__meta-item">
                            <x-heroicon-o-calendar-days class="fsv-hero__meta-icon" />
                            <span>تقديم: {{ $request->created_at->format('Y/m/d') }}</span>
                        </div>
                    @endif

                    @if ($request->decided_at)
                        <div class="fsv-hero__meta-item">
                            <x-heroicon-o-check-circle class="fsv-hero__meta-icon" />
                            <span>قرار: {{ $request->decided_at->format('Y/m/d') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        {{-- INFOLIST: request details + decision details --}}
        @if($this->hasInfolist())
            {{ $this->infolist }}
        @endif
    </div>

    @push('styles')
    <style>
        /* ---------- Hero card ---------- */
        .figma-suspension-view .fsv-hero {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
            padding: 25px;
            background: #ffffff;
            border: 1px solid #e4e4e7;
            border-radius: 12px;
        }
        .dark .figma-suspension-view .fsv-hero {
            background: rgb(24 24 27);
            border-color: rgb(63 63 70);
        }

        .figma-suspension-view .fsv-hero__identity {
            display: flex;
            flex-direction: column;
            gap: 7.5px;
            flex: 1;
            min-width: 0;
            order: 1;
            align-items: flex-start;
            text-align: right;
        }
        .figma-suspension-view .fsv-hero__name-row {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .figma-suspension-view .fsv-hero__name-row .fsv-hero__name { margin-inline-end: 12px; }

        .figma-suspension-view .fsv-hero__name {
            font-size: 24px;
            line-height: 32px;
            font-weight: 700;
            color: #09090b;
            margin: 0;
        }
        .dark .figma-suspension-view .fsv-hero__name { color: #fafafa; }

        .figma-suspension-view .fsv-hero__enrollment {
            display: inline-flex;
            align-items: baseline;
            gap: 8px;
            margin-top: 2px;
        }
        .figma-suspension-view .fsv-hero__enrollment-label {
            font-size: 14px;
            color: #09090b;
            font-weight: 500;
        }
        .dark .figma-suspension-view .fsv-hero__enrollment-label { color: #fafafa; }
        .figma-suspension-view .fsv-hero__enrollment-value {
            font-size: 22px;
            font-weight: 700;
            color: #09090b;
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.01em;
        }
        .dark .figma-suspension-view .fsv-hero__enrollment-value { color: #fafafa; }

        /* Muted pills */
        .figma-suspension-view .fsv-status-pill {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border-radius: 4px;
            font-size: 12px;
            line-height: 16px;
            font-weight: 700;
        }
        .figma-suspension-view .fsv-status-pill--success { background: #f0fdf4; border: 1px solid #dcfce7; color: #166534; }
        .figma-suspension-view .fsv-status-pill--danger  { background: #fef2f2; border: 1px solid #fee2e2; color: #991b1b; }
        .figma-suspension-view .fsv-status-pill--warning { background: #fffbeb; border: 1px solid #fef3c7; color: #92400e; }
        .figma-suspension-view .fsv-status-pill--info    { background: #eff6ff; border: 1px solid #dbeafe; color: #1e40af; }
        .figma-suspension-view .fsv-status-pill--gray    { background: #fafafa; border: 1px solid #e4e4e7; color: #52525b; }
        .dark .figma-suspension-view .fsv-status-pill--success { background: rgba(34, 197, 94, 0.08); border-color: rgba(34, 197, 94, 0.18); color: #86efac; }
        .dark .figma-suspension-view .fsv-status-pill--danger  { background: rgba(239, 68, 68, 0.08); border-color: rgba(239, 68, 68, 0.18); color: #fca5a5; }
        .dark .figma-suspension-view .fsv-status-pill--warning { background: rgba(245, 158, 11, 0.08); border-color: rgba(245, 158, 11, 0.18); color: #fcd34d; }
        .dark .figma-suspension-view .fsv-status-pill--info    { background: rgba(59, 130, 246, 0.08); border-color: rgba(59, 130, 246, 0.18); color: #93c5fd; }

        .figma-suspension-view .fsv-role-pill {
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
        .dark .figma-suspension-view .fsv-role-pill {
            background: rgba(244, 244, 245, 0.06);
            border-color: rgb(63 63 70);
            color: #d4d4d8;
        }
        .figma-suspension-view .fsv-role-pill--info { color: #1e40af; border-color: #dbeafe; background: #eff6ff; }
        .dark .figma-suspension-view .fsv-role-pill--info { color: #93c5fd; background: rgba(59, 130, 246, 0.08); border-color: rgba(59, 130, 246, 0.18); }

        .figma-suspension-view .fsv-hero__meta {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .figma-suspension-view .fsv-hero__meta-item {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 14px;
            line-height: 20px;
            color: #52525b; /* H-1: bumped from #71717a for WCAG AA (light mode) */
        }
        .figma-suspension-view .fsv-hero__meta-icon { width: 14px; height: 14px; color: #52525b; }
        .dark .figma-suspension-view .fsv-hero__meta-item,
        .dark .figma-suspension-view .fsv-hero__meta-icon { color: #71717a; }

        /* ---------- Filament Section cards ---------- */
        .figma-suspension-view .fi-section {
            background: #ffffff;
            border: 1px solid #e4e4e7;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: none;
        }
        .dark .figma-suspension-view .fi-section {
            background: rgb(24 24 27);
            border-color: rgb(63 63 70);
        }
        .figma-suspension-view .fi-section-header {
            background: rgba(244, 244, 245, 0.5);
            border-bottom: 1px solid #e4e4e7;
            padding: 12px 20px 13px;
        }
        .dark .figma-suspension-view .fi-section-header {
            background: rgba(63, 63, 70, 0.3);
            border-bottom-color: rgb(63 63 70);
        }
        .figma-suspension-view .fi-section-header-heading {
            font-family: inherit;
            font-size: 16px;
            line-height: 24px;
            font-weight: 700;
            color: #09090b;
            text-align: right;
        }
        .dark .figma-suspension-view .fi-section-header-heading { color: #fafafa; }
        .figma-suspension-view .fi-section-content,
        .figma-suspension-view .fi-section-content-ctn { padding: 20px; }

        /* Row separators */
        .figma-suspension-view .fi-section-content .fi-in-entry-wrp,
        .figma-suspension-view .fi-section-content .fi-in-text-entry {
            padding-top: 8px;
            padding-bottom: 9px;
            border-bottom: 1px solid #e4e4e7;
        }
        .figma-suspension-view .fi-section-content .fi-in-entry-wrp:last-child,
        .figma-suspension-view .fi-section-content .fi-in-text-entry:last-child {
            border-bottom: 0;
            padding-bottom: 8px;
        }
        .dark .figma-suspension-view .fi-section-content .fi-in-entry-wrp,
        .dark .figma-suspension-view .fi-section-content .fi-in-text-entry {
            border-bottom-color: rgb(63 63 70);
        }

        .figma-suspension-view .fi-in-entry-label {
            font-size: 14px;
            line-height: 20px;
            color: #71717a;
            font-weight: 400;
        }
        .figma-suspension-view .fi-in-entry .fi-in-text {
            font-size: 14px;
            line-height: 20px;
            color: #09090b;
        }
        .dark .figma-suspension-view .fi-in-entry .fi-in-text { color: #fafafa; }
        .figma-suspension-view .fi-in-entry .fi-icon { color: #71717a; }

        /* Pending callout (shown only when status = Pending) */
        .figma-suspension-view .fsv-pending-callout {
            background: #fffbeb !important;
            border: 1px solid #fef3c7 !important;
            border-radius: 8px;
            padding: 13px !important;
            margin-top: 16px;
        }
        .figma-suspension-view .fsv-pending-callout .fi-in-text,
        .figma-suspension-view .fsv-pending-callout .fi-in-text * { color: #92400e !important; }
        .figma-suspension-view .fsv-pending-callout .fi-icon { color: #92400e !important; }
        .dark .figma-suspension-view .fsv-pending-callout {
            background: rgba(245, 158, 11, 0.10) !important;
            border-color: rgba(245, 158, 11, 0.25) !important;
        }
        .dark .figma-suspension-view .fsv-pending-callout .fi-in-text,
        .dark .figma-suspension-view .fsv-pending-callout .fi-in-text *,
        .dark .figma-suspension-view .fsv-pending-callout .fi-icon { color: #fcd34d !important; }

    </style>
    @endpush
</x-filament-panels::page>
