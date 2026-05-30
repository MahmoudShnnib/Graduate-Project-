{{-- Published from filament/schemas v5: components/form.blade.php
    Modified: added `novalidate` so server-side Arabic validation renders
    inline instead of the browser-native English HTML5 tooltip (H-7 audit fix).
    See _brain/daily-notes/2026-05-16-wave2-h-and-m-batch.md. --}}
<form
    novalidate
    {{
        $attributes
            ->merge([
                'id' => $getId(),
                'wire:submit' => $getLivewireSubmitHandler(),
            ], escape: false)
            ->merge($getExtraAttributes(), escape: false)
            ->class([
                'fi-sc-form',
                'fi-dense' => $isDense(),
            ])
    }}
>
    {{ $getChildSchema($schemaComponent::HEADER_SCHEMA_KEY) }}

    {{ $getChildSchema() }}

    {{ $getChildSchema($schemaComponent::FOOTER_SCHEMA_KEY) }}
</form>
