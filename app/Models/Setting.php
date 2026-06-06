<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Default values applied when a row is missing.
     * Used as the fallback for Setting::get() and as the seed payload.
     *
     * @var array<string, string>
     */
    public const DEFAULTS = [
        'college_name' => 'كلية التقنية الإلكترونية – طرابلس',
        'office_name' => 'مكتب الدراسات العليا',
        'director_name' => 'د. بشير محمد الجللي',
        'dean_name' => 'د. هشام الأمين الحجاجي',
        'contact_phone' => '+218 21 000 0000',
        'contact_whatsapp' => '218910000000',
        'contact_email' => 'info@cet.edu.ly',
        'contact_address' => 'طرابلس — ليبيا',
        'contact_hours' => 'الأحد — الخميس: 9ص — 3م',
    ];

    public static function get(string $key, ?string $default = null): ?string
    {
        $row = static::query()->where('key', $key)->first();

        return $row?->value ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function set(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Returns every default key with either the stored value or the hardcoded default.
     *
     * @return array<string, ?string>
     */
    public static function allKeyed(): array
    {
        $rows = static::query()->pluck('value', 'key')->toArray();

        return array_merge(self::DEFAULTS, $rows);
    }
}
