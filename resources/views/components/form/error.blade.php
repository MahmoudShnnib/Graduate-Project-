@props(['name'])

@error($name)
<p class="mt-2 text-sm font-medium text-red-600">
    {{ $message }}
</p>
@enderror
