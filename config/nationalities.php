<?php

/*
|--------------------------------------------------------------------------
| Nationalities (country picker)
|--------------------------------------------------------------------------
|
| Single source of truth for the public /apply nationality picker AND the
| server-side validation in RegistrationController. The stored value is the
| Arabic country name (e.g. ليبيا). Libya is intentionally first so it is the
| default selection. `flag` is an emoji so no image assets are required.
|
| Keep this list and the validation `Rule::in(...)` aligned — the controller
| derives its allow-list from `array_column(config('nationalities.list'), 'name')`.
|
*/

return [

    // The value treated as "Libyan" — drives the 12-digit national-number rule.
    'default' => 'ليبيا',

    'list' => [
        ['name' => 'ليبيا', 'flag' => '🇱🇾'],
        ['name' => 'تونس', 'flag' => '🇹🇳'],
        ['name' => 'الجزائر', 'flag' => '🇩🇿'],
        ['name' => 'مصر', 'flag' => '🇪🇬'],
        ['name' => 'المغرب', 'flag' => '🇲🇦'],
        ['name' => 'السودان', 'flag' => '🇸🇩'],
        ['name' => 'موريتانيا', 'flag' => '🇲🇷'],
        ['name' => 'السعودية', 'flag' => '🇸🇦'],
        ['name' => 'الإمارات', 'flag' => '🇦🇪'],
        ['name' => 'قطر', 'flag' => '🇶🇦'],
        ['name' => 'الكويت', 'flag' => '🇰🇼'],
        ['name' => 'البحرين', 'flag' => '🇧🇭'],
        ['name' => 'عُمان', 'flag' => '🇴🇲'],
        ['name' => 'اليمن', 'flag' => '🇾🇪'],
        ['name' => 'الأردن', 'flag' => '🇯🇴'],
        ['name' => 'فلسطين', 'flag' => '🇵🇸'],
        ['name' => 'سوريا', 'flag' => '🇸🇾'],
        ['name' => 'لبنان', 'flag' => '🇱🇧'],
        ['name' => 'العراق', 'flag' => '🇮🇶'],
        ['name' => 'الصومال', 'flag' => '🇸🇴'],
        ['name' => 'جيبوتي', 'flag' => '🇩🇯'],
        ['name' => 'جزر القمر', 'flag' => '🇰🇲'],
        ['name' => 'تشاد', 'flag' => '🇹🇩'],
        ['name' => 'النيجر', 'flag' => '🇳🇪'],
        ['name' => 'مالي', 'flag' => '🇲🇱'],
        ['name' => 'نيجيريا', 'flag' => '🇳🇬'],
        ['name' => 'تركيا', 'flag' => '🇹🇷'],
        ['name' => 'إيران', 'flag' => '🇮🇷'],
        ['name' => 'باكستان', 'flag' => '🇵🇰'],
        ['name' => 'الهند', 'flag' => '🇮🇳'],
        ['name' => 'بنغلاديش', 'flag' => '🇧🇩'],
        ['name' => 'أفغانستان', 'flag' => '🇦🇫'],
        ['name' => 'إندونيسيا', 'flag' => '🇮🇩'],
        ['name' => 'ماليزيا', 'flag' => '🇲🇾'],
        ['name' => 'بريطانيا', 'flag' => '🇬🇧'],
        ['name' => 'فرنسا', 'flag' => '🇫🇷'],
        ['name' => 'ألمانيا', 'flag' => '🇩🇪'],
        ['name' => 'إيطاليا', 'flag' => '🇮🇹'],
        ['name' => 'إسبانيا', 'flag' => '🇪🇸'],
        ['name' => 'الولايات المتحدة', 'flag' => '🇺🇸'],
        ['name' => 'كندا', 'flag' => '🇨🇦'],
        ['name' => 'أخرى', 'flag' => '🌍'],
    ],
];
