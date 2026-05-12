@php
    /** @var \App\Models\Student $record */
    $degreePath = $record->degree_image;
    $degreeUrl = $degreePath ? asset('storage/'.$degreePath) : null;
    $isPdf = $degreePath && str_ends_with(strtolower($degreePath), '.pdf');
@endphp

<div x-data="{ preview: false }" class="fsv-degree-doc">
    @if (! $degreeUrl)
        <div class="fsv-degree-doc__empty">
            <x-heroicon-o-document class="fsv-degree-doc__empty-icon" />
            <p>لم يتم رفع الوثيقة العلمية</p>
        </div>
    @elseif ($isPdf)
        <a href="{{ $degreeUrl }}" target="_blank" rel="noopener" class="fsv-degree-doc__pdf">
            <x-heroicon-o-document-text class="fsv-degree-doc__pdf-icon" />
            <div class="fsv-degree-doc__pdf-meta">
                <span class="fsv-degree-doc__pdf-title">الوثيقة العلمية</span>
                <span class="fsv-degree-doc__pdf-sub">ملف PDF — اضغط للفتح</span>
            </div>
            <x-heroicon-o-arrow-top-right-on-square class="fsv-degree-doc__pdf-arrow" />
        </a>
    @else
        <div class="fsv-degree-doc__image-wrap">
            <button type="button" @click="preview = true" class="fsv-degree-doc__image-btn" aria-label="عرض الوثيقة العلمية بالحجم الكامل">
                <img src="{{ $degreeUrl }}" alt="الوثيقة العلمية" class="fsv-degree-doc__image" />
                <span class="fsv-degree-doc__image-overlay">
                    <x-heroicon-o-magnifying-glass-plus class="fsv-degree-doc__image-overlay-icon" />
                </span>
            </button>

            <button type="button" @click="preview = true" class="fsv-btn fsv-btn--primary fsv-btn--full">
                <span>عرض بالحجم الكامل</span>
                <x-heroicon-o-magnifying-glass-plus class="fsv-btn__icon" />
            </button>
        </div>

        <div
            x-cloak
            x-show="preview"
            x-transition.opacity
            @keydown.escape.window="preview = false"
            class="fsv-preview-modal"
            role="dialog"
            aria-modal="true"
            aria-label="الوثيقة العلمية"
        >
            <div class="fsv-preview-modal__backdrop" @click="preview = false"></div>
            <div class="fsv-preview-modal__panel">
                <button type="button" @click="preview = false" class="fsv-preview-modal__close" aria-label="إغلاق">
                    <x-heroicon-o-x-mark class="fsv-preview-modal__close-icon" />
                </button>
                <img src="{{ $degreeUrl }}" alt="الوثيقة العلمية" class="fsv-preview-modal__image" />
            </div>
        </div>
    @endif
</div>
