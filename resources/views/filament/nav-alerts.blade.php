{{-- Marks a navigation GROUP with an alert dot when any of its (possibly
     collapsed) child items has a pending badge. Mirrors existing child badges
     — no duplicated count logic. SPA-safe via livewire:navigated. --}}
<style>
    .fi-sidebar-group.fi-nav-alert .fi-sidebar-group-label::after {
        content: "";
        display: inline-block;
        inline-size: 0.5rem;
        block-size: 0.5rem;
        margin-inline-start: 0.45rem;
        border-radius: 9999px;
        background: #f59e0b; /* amber-500 — change to #22c55e for green */
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
        vertical-align: middle;
        animation: fiNavAlertPulse 2s ease-in-out infinite;
    }
    @keyframes fiNavAlertPulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.35; } }
    @media (prefers-reduced-motion: reduce) {
        .fi-sidebar-group.fi-nav-alert .fi-sidebar-group-label::after { animation: none; }
    }
</style>
<script>
    (function () {
        if (window.__fiNavAlerts) { window.__fiNavAlerts(); return; }
        window.__fiNavAlerts = function () {
            document.querySelectorAll('.fi-sidebar-group').forEach(function (group) {
                var hasBadge = !! group.querySelector('.fi-sidebar-item-badge-ctn');
                group.classList.toggle('fi-nav-alert', hasBadge);
            });
        };
        var run = window.__fiNavAlerts;
        document.addEventListener('DOMContentLoaded', run);
        document.addEventListener('livewire:navigated', run);
        document.addEventListener('livewire:init', run);
        setTimeout(run, 250);
        run();
    })();
</script>
