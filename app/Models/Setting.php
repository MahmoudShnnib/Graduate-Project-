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

    /**
     * Per-request memoization of the whole key/value table.
     * The configured cache store is `database`, so cache()->remember() would
     * itself fire a DB query per lookup — a static cache is the only way to
     * guarantee at most one query per request regardless of how many times
     * Setting::get() is called (e.g. PDF templates call it ~6 times).
     *
     * @var array<string, ?string>|null
     */
    private static ?array $cached = null;

    public static function get(string $key, ?string $default = null): ?string
    {
        self::$cached ??= static::query()->pluck('value', 'key')->toArray();

        return self::$cached[$key] ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function set(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::$cached = null);
        static::deleted(fn () => self::$cached = null);
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
