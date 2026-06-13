@if(auth()->user()?->role === 'super_admin')
    @livewire('admin-activity-bell')
@endif
