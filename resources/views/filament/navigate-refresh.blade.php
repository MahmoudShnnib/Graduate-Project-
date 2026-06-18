{{--
    Panel-wide fix for Livewire wire:navigate (SPA) back/forward staleness.

    Clicking a link forward re-renders a page fresh, but pressing the browser Back/Forward
    button restores a cached DOM snapshot WITHOUT revalidating — so server-rendered counts,
    statuses and table rows go stale until a hard refresh (e.g. the grades page hero
    "X / Y درجة مدخلة" and the grade-status column after approving a grade elsewhere).

    On a history restore (popstate) we re-sync only the app's own page Livewire component(s);
    Filament's own chrome (topbar/sidebar/notifications) handles its own refresh. Forward
    link navigation is untouched, so there is no extra cost on normal navigation. The install
    guard keeps the listeners singular even though this hook re-renders on every page.
--}}
<script>
    (function () {
        if (window.__cetNavRefreshInstalled) {
            return;
        }
        window.__cetNavRefreshInstalled = true;

        let restoringFromHistory = false;

        window.addEventListener('popstate', function () {
            restoringFromHistory = true;
        });

        document.addEventListener('livewire:navigated', function () {
            if (! restoringFromHistory) {
                return;
            }
            restoringFromHistory = false;

            if (! window.Livewire) {
                return;
            }

            window.Livewire.all()
                .filter(function (component) {
                    return (component.name || '').startsWith('App\\');
                })
                .forEach(function (component) {
                    try {
                        component.$wire.$refresh();
                    } catch (e) {}
                });
        });
    })();
</script>
