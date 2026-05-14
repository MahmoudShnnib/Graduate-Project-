<x-filament-panels::page>
    {{-- ============================================================
         /admin/specializations/{id} — single-card layout
         Figma view-page design language (one consolidated card).
         Reference: _brain/project-context/figma-view-page-design.md
         Backup: _backups/specialization-view-2026-05-13/
       ============================================================ --}}

    @php
        /** @var \App\Models\Specialization $specialization */
        $specialization = $this->record;
        $specializationName = $specialization->name ?? '—';
        $codeLabel = $specialization->code !== null ? 'P'.$specialization->code : '—';
        $coursesCount = $specialization->courses()->count();
        $studentsCount = $specialization->students()->count();
        $editUrl = \App\Filament\Resources\Specializations\SpecializationResource::getUrl('edit', ['record' => $specialization]);
    @endphp

    <div class="figma-specialization-view space-y-6" dir="rtl">

        {{-- ONE consolidated card: identity header + KPI footer --}}
        <section class="fsv-card">
            <div class="fsv-card__header">
                <div class="fsv-card__actions">
                    <a href="{{ $editUrl }}" class="fsv-btn fsv-btn--primary">
                        <span>تعديل</span>
                        <x-heroicon-o-pencil-square class="fsv-btn__icon" />
                    </a>
                </div>

                <div class="fsv-card__identity">
                    <h1 class="fsv-card__name">{{ $specializationName }}</h1>
                    <div class="fsv-card__subtitle">
                        <span class="fsv-card__subtitle-label">رمز التخصص:</span>
                        <span class="fsv-card__subtitle-value">{{ $codeLabel }}</span>
                    </div>
                </div>
            </div>

            <div class="fsv-card__kpis">
                <div class="fsv-kpi">
                    <span class="fsv-kpi__label">عدد المقررات</span>
                    <span class="fsv-kpi__value">{{ $coursesCount }}</span>
                </div>
                <div class="fsv-kpi__divider"></div>
                <div class="fsv-kpi">
                    <span class="fsv-kpi__label">عدد الطلاب</span>
                    <span class="fsv-kpi__value">{{ $studentsCount }}</span>
                </div>
            </div>
        </section>

        {{-- Courses relation manager (rendered by Filament; styled as a card via CSS) --}}
        {{ $this->content }}
    </div>

    @push('styles')
    <style>
        /* ---------- One consolidated card ---------- */
        .figma-specialization-view .fsv-card {
            background: #ffffff;
            border: 1px solid #e4e4e7;
            border-radius: 12px;
            overflow: hidden;
        }
        .dark .figma-specialization-view .fsv-card {
            background: rgb(24 24 27);
            border-color: rgb(63 63 70);
        }

        /* Header band (identity + actions) */
        .figma-specialization-view .fsv-card__header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
            padding: 24px 28px;
        }

        .figma-specialization-view .fsv-card__actions {
            display: flex;
            align-items: center;
            gap: 8px;
            order: 2; /* visually-left in RTL */
        }

        .figma-specialization-view .fsv-card__identity {
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex: 1;
            min-width: 0;
            order: 1;
            align-items: flex-start; /* right-anchor in RTL */
            text-align: right;
        }
        .figma-specialization-view .fsv-card__name {
            font-size: 26px;
            line-height: 34px;
            font-weight: 700;
            color: #09090b;
            margin: 0;
        }
        .dark .figma-specialization-view .fsv-card__name { color: #fafafa; }

        .figma-specialization-view .fsv-card__subtitle {
            display: inline-flex;
            align-items: baseline;
            gap: 8px;
        }
        .figma-specialization-view .fsv-card__subtitle-label {
            font-size: 14px;
            color: #71717a;
            font-weight: 500;
        }
        .figma-specialization-view .fsv-card__subtitle-value {
            font-size: 18px;
            font-weight: 700;
            color: #09090b;
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.01em;
        }
        .dark .figma-specialization-view .fsv-card__subtitle-value { color: #fafafa; }

        /* KPI footer (counts) — inside the SAME card, separated by a hairline */
        .figma-specialization-view .fsv-card__kpis {
            display: flex;
            align-items: stretch;
            justify-content: stretch;
            border-top: 1px solid #e4e4e7;
            background: rgba(244, 244, 245, 0.4);
        }
        .dark .figma-specialization-view .fsv-card__kpis {
            border-top-color: rgb(63 63 70);
            background: rgba(63, 63, 70, 0.25);
        }
        .figma-specialization-view .fsv-kpi {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 22px 18px;
        }
        .figma-specialization-view .fsv-kpi__label {
            font-size: 13px;
            color: #71717a;
            font-weight: 500;
        }
        .figma-specialization-view .fsv-kpi__value {
            font-size: 30px;
            line-height: 36px;
            font-weight: 700;
            color: #09090b;
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.02em;
        }
        .dark .figma-specialization-view .fsv-kpi__value { color: #fafafa; }
        .figma-specialization-view .fsv-kpi__divider {
            width: 1px;
            background: #e4e4e7;
            margin: 14px 0;
        }
        .dark .figma-specialization-view .fsv-kpi__divider { background: rgb(63 63 70); }

        /* ---------- Buttons ---------- */
        .figma-specialization-view .fsv-btn {
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
        .figma-specialization-view .fsv-btn__icon { width: 16px; height: 16px; }

        .figma-specialization-view .fsv-btn--primary {
            background: #1F4E79;
            color: #ffffff;
            border: 1px solid #1F4E79;
            box-shadow: 0 1px 2px rgba(31, 78, 121, 0.12);
        }
        .figma-specialization-view .fsv-btn--primary:hover {
            background: #16395a;
            border-color: #16395a;
            color: #ffffff;
        }
        .dark .figma-specialization-view .fsv-btn--primary {
            background: #2f6aa8;
            border-color: #2f6aa8;
            color: #ffffff;
        }
        .dark .figma-specialization-view .fsv-btn--primary:hover {
            background: #1F4E79;
            border-color: #1F4E79;
        }

        /* ---------- Relation Manager / courses table styled as a card ---------- */
        .figma-specialization-view .fi-resource-relation-manager,
        .figma-specialization-view .fi-ta-ctn {
            background: #ffffff;
            border: 1px solid #e4e4e7;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: none;
        }
        .dark .figma-specialization-view .fi-resource-relation-manager,
        .dark .figma-specialization-view .fi-ta-ctn {
            background: rgb(24 24 27);
            border-color: rgb(63 63 70);
        }
        .figma-specialization-view .fi-ta-header {
            background: rgba(244, 244, 245, 0.5);
            border-bottom: 1px solid #e4e4e7;
            padding: 12px 20px 13px;
        }
        .dark .figma-specialization-view .fi-ta-header {
            background: rgba(63, 63, 70, 0.3);
            border-bottom-color: rgb(63 63 70);
        }
        .figma-specialization-view .fi-ta-header-heading {
            font-size: 16px;
            line-height: 24px;
            font-weight: 700;
        }

        /* Responsive: stack KPIs on narrow screens */
        @media (max-width: 640px) {
            .figma-specialization-view .fsv-card__kpis {
                flex-direction: column;
            }
            .figma-specialization-view .fsv-kpi__divider {
                width: auto;
                height: 1px;
                margin: 0 14px;
            }
        }
    </style>
    @endpush
</x-filament-panels::page>
