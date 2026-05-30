<x-filament-panels::page>
    {{-- ============================================================
         /admin/users/{id} — Figma view-page design language
         Mirrors /admin/students/{id}. Backup: _backups/user-view-2026-05-13/
       ============================================================ --}}

    @php
        /** @var \App\Models\User $user */
        $user = $this->record;
        $userName = $user->name ?? '—';
        $roleLabel = match ($user->role) {
            'super_admin' => 'مشرف عام',
            'secretary' => 'سكرتير',
            'faculty' => 'عضو هيئة تدريس',
            default => $user->role ?: '—',
        };
        $roleColor = match ($user->role) {
            'super_admin' => 'danger',
            'secretary' => 'warning',
            'faculty' => 'success',
            default => 'gray',
        };
        $statusLabel = $user->is_active ? 'نشط' : 'غير نشط';
        $statusColor = $user->is_active ? 'success' : 'danger';
        $editUrl = \App\Filament\Resources\Users\UserResource::getUrl('edit', ['record' => $user]);
    @endphp

    <div class="figma-user-view space-y-6" dir="rtl">

        {{-- HERO CARD --}}
        <section class="fsv-hero">
            <div class="fsv-hero__actions">
                <a href="{{ $editUrl }}" class="fsv-btn fsv-btn--primary">
                    <span>تعديل</span>
                    <x-heroicon-o-pencil-square class="fsv-btn__icon" />
                </a>
            </div>

            <div class="fsv-hero__identity">
                <div class="fsv-hero__name-row">
                    <h1 class="fsv-hero__name">{{ $userName }}</h1>

                    <span @class([
                        'fsv-status-pill',
                        "fsv-status-pill--{$statusColor}",
                    ])>{{ $statusLabel }}</span>

                    <span @class([
                        'fsv-role-pill',
                        "fsv-role-pill--{$roleColor}",
                    ])>{{ $roleLabel }}</span>
                </div>

                <div class="fsv-hero__meta">
                    @if ($user->email)
                        <div class="fsv-hero__meta-item">
                            <x-heroicon-o-envelope class="fsv-hero__meta-icon" />
                            <span>{{ $user->email }}</span>
                        </div>
                    @endif

                    @if ($user->phone)
                        <div class="fsv-hero__meta-item">
                            <x-heroicon-o-phone class="fsv-hero__meta-icon" />
                            <span>{{ $user->phone }}</span>
                        </div>
                    @endif

                    @if ($user->academic_rank)
                        <div class="fsv-hero__meta-item">
                            <x-heroicon-o-star class="fsv-hero__meta-icon" />
                            <span>{{ $user->academic_rank }}</span>
                        </div>
                    @endif

                    @if ($user->institution)
                        <div class="fsv-hero__meta-item">
                            <x-heroicon-o-building-library class="fsv-hero__meta-icon" />
                            <span>{{ $user->institution }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        {{-- INFOLIST: 2-column layout with restyled section cards --}}
        @if($this->hasInfolist())
            {{ $this->infolist }}
        @endif
    </div>

    @push('styles')
    <style>
        /* ---------- Hero card ---------- */
        .figma-user-view .fsv-hero {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
            padding: 25px;
            background: #ffffff;
            border: 1px solid #e4e4e7;
            border-radius: 12px;
        }
        .dark .figma-user-view .fsv-hero {
            background: rgb(24 24 27);
            border-color: rgb(63 63 70);
        }

        .figma-user-view .fsv-hero__actions {
            display: flex;
            align-items: center;
            gap: 8px;
            order: 2;
        }

        .figma-user-view .fsv-btn {
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
        .figma-user-view .fsv-btn__icon { width: 16px; height: 16px; }

        .figma-user-view .fsv-btn--primary {
            background: #1F4E79;
            color: #ffffff;
            border: 1px solid #1F4E79;
            box-shadow: 0 1px 2px rgba(31, 78, 121, 0.12);
        }
        .figma-user-view .fsv-btn--primary:hover { background: #16395a; border-color: #16395a; color: #ffffff; }
        .dark .figma-user-view .fsv-btn--primary { background: #2f6aa8; border-color: #2f6aa8; color: #ffffff; }
        .dark .figma-user-view .fsv-btn--primary:hover { background: #1F4E79; border-color: #1F4E79; }

        .figma-user-view .fsv-hero__identity {
            display: flex;
            flex-direction: column;
            gap: 7.5px;
            flex: 1;
            min-width: 0;
            order: 1;
            align-items: flex-start;
            text-align: right;
        }
        .figma-user-view .fsv-hero__name-row {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .figma-user-view .fsv-hero__name-row .fsv-hero__name { margin-inline-end: 12px; }

        .figma-user-view .fsv-hero__name {
            font-size: 24px;
            line-height: 32px;
            font-weight: 700;
            color: #09090b;
            margin: 0;
        }
        .dark .figma-user-view .fsv-hero__name { color: #fafafa; }

        .figma-user-view .fsv-hero__enrollment {
            display: inline-flex;
            align-items: baseline;
            gap: 8px;
            margin-top: 2px;
        }
        .figma-user-view .fsv-hero__enrollment-value {
            font-size: 16px;
            font-weight: 600;
            color: #71717a;
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.01em;
        }
        .dark .figma-user-view .fsv-hero__enrollment-value { color: #a1a1aa; }

        .figma-user-view .fsv-status-pill {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border-radius: 4px;
            font-size: 12px;
            line-height: 16px;
            font-weight: 700;
        }
        .figma-user-view .fsv-status-pill--success { background: #f0fdf4; border: 1px solid #dcfce7; color: #166534; }
        .figma-user-view .fsv-status-pill--danger  { background: #fef2f2; border: 1px solid #fee2e2; color: #991b1b; }
        .figma-user-view .fsv-status-pill--warning { background: #fffbeb; border: 1px solid #fef3c7; color: #92400e; }
        .figma-user-view .fsv-status-pill--info    { background: #eff6ff; border: 1px solid #dbeafe; color: #1e40af; }
        .figma-user-view .fsv-status-pill--gray    { background: #fafafa; border: 1px solid #e4e4e7; color: #52525b; }
        .dark .figma-user-view .fsv-status-pill--success { background: rgba(34, 197, 94, 0.08); border-color: rgba(34, 197, 94, 0.18); color: #86efac; }
        .dark .figma-user-view .fsv-status-pill--danger  { background: rgba(239, 68, 68, 0.08); border-color: rgba(239, 68, 68, 0.18); color: #fca5a5; }
        .dark .figma-user-view .fsv-status-pill--warning { background: rgba(245, 158, 11, 0.08); border-color: rgba(245, 158, 11, 0.18); color: #fcd34d; }
        .dark .figma-user-view .fsv-status-pill--info    { background: rgba(59, 130, 246, 0.08); border-color: rgba(59, 130, 246, 0.18); color: #93c5fd; }

        /* Role pill — softer, less saturated palette so it sits quieter next to the status pill */
        .figma-user-view .fsv-role-pill {
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
        .dark .figma-user-view .fsv-role-pill {
            background: rgba(244, 244, 245, 0.06);
            border-color: rgb(63 63 70);
            color: #d4d4d8;
        }
        .figma-user-view .fsv-role-pill--success { color: #166534; border-color: #dcfce7; background: #f0fdf4; }
        .figma-user-view .fsv-role-pill--danger  { color: #991b1b; border-color: #fee2e2; background: #fef2f2; }
        .figma-user-view .fsv-role-pill--warning { color: #92400e; border-color: #fef3c7; background: #fffbeb; }
        .figma-user-view .fsv-role-pill--info    { color: #1e40af; border-color: #dbeafe; background: #eff6ff; }
        .figma-user-view .fsv-role-pill--gray    { color: #52525b; border-color: #e4e4e7; background: #fafafa; }
        .dark .figma-user-view .fsv-role-pill--success { color: #86efac; background: rgba(34, 197, 94, 0.08); border-color: rgba(34, 197, 94, 0.18); }
        .dark .figma-user-view .fsv-role-pill--danger  { color: #fca5a5; background: rgba(239, 68, 68, 0.08); border-color: rgba(239, 68, 68, 0.18); }
        .dark .figma-user-view .fsv-role-pill--warning { color: #fcd34d; background: rgba(245, 158, 11, 0.08); border-color: rgba(245, 158, 11, 0.18); }
        .dark .figma-user-view .fsv-role-pill--info    { color: #93c5fd; background: rgba(59, 130, 246, 0.08); border-color: rgba(59, 130, 246, 0.18); }

        .figma-user-view .fsv-hero__meta {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .figma-user-view .fsv-hero__meta-item {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 14px;
            line-height: 20px;
            color: #52525b; /* H-1: bumped from #71717a for WCAG AA (light mode) */
        }
        .figma-user-view .fsv-hero__meta-icon { width: 14px; height: 14px; color: #52525b; }
        .dark .figma-user-view .fsv-hero__meta-item,
        .dark .figma-user-view .fsv-hero__meta-icon { color: #71717a; }

        /* ---------- Filament Section cards ---------- */
        .figma-user-view .fi-section {
            background: #ffffff;
            border: 1px solid #e4e4e7;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: none;
        }
        .dark .figma-user-view .fi-section {
            background: rgb(24 24 27);
            border-color: rgb(63 63 70);
        }
        .figma-user-view .fi-section-header {
            background: rgba(244, 244, 245, 0.5);
            border-bottom: 1px solid #e4e4e7;
            padding: 12px 20px 13px;
        }
        .dark .figma-user-view .fi-section-header {
            background: rgba(63, 63, 70, 0.3);
            border-bottom-color: rgb(63 63 70);
        }
        .figma-user-view .fi-section-header-heading {
            font-family: inherit;
            font-size: 16px;
            line-height: 24px;
            font-weight: 700;
            color: #09090b;
            text-align: right;
        }
        .dark .figma-user-view .fi-section-header-heading { color: #fafafa; }
        .figma-user-view .fi-section-content,
        .figma-user-view .fi-section-content-ctn { padding: 20px; }

        /* Row separators between infolist entries */
        .figma-user-view .fi-section-content .fi-in-entry-wrp,
        .figma-user-view .fi-section-content .fi-in-text-entry {
            padding-top: 8px;
            padding-bottom: 9px;
            border-bottom: 1px solid #e4e4e7;
        }
        .figma-user-view .fi-section-content .fi-in-entry-wrp:last-child,
        .figma-user-view .fi-section-content .fi-in-text-entry:last-child {
            border-bottom: 0;
            padding-bottom: 8px;
        }
        .dark .figma-user-view .fi-section-content .fi-in-entry-wrp,
        .dark .figma-user-view .fi-section-content .fi-in-text-entry {
            border-bottom-color: rgb(63 63 70);
        }

        .figma-user-view .fi-in-entry-label {
            font-size: 14px;
            line-height: 20px;
            color: #71717a;
            font-weight: 400;
        }
        .figma-user-view .fi-in-entry .fi-in-text {
            font-size: 14px;
            line-height: 20px;
            color: #09090b;
        }
        .dark .figma-user-view .fi-in-entry .fi-in-text { color: #fafafa; }
        .figma-user-view .fi-in-entry .fi-icon { color: #71717a; }

    </style>
    @endpush
</x-filament-panels::page>
