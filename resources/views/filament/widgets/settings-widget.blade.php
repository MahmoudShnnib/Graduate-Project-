<x-filament-widgets::widget>
    <div class="flex items-center justify-end gap-2">
        <x-filament::button
            wire:click="mountAction('editSettings')"
            icon="heroicon-o-cog-6-tooth"
            color="gray"
            size="sm"
            tag="button"
            tooltip="تعديل إعدادات النظام"
        >
            إعدادات النظام
        </x-filament::button>

        <x-filament-actions::modals />
    </div>
</x-filament-widgets::widget>
