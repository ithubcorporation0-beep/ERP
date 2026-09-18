<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * A flat key-value store for app-wide configuration (see App\Support\SettingKeys
 * for the known keys). `value` is a JSON column, cast to 'array' — which,
 * despite the name, round-trips any JSON-encodable PHP value (string, int,
 * bool, array) via json_encode()/json_decode(), not just arrays.
 *
 * The company logo is the one exception: it's a file, so it's stored as
 * media (via HasMedia) attached to the row keyed SettingKeys::COMPANY_LOGO,
 * on the same private disk as every other upload in this app, rather than
 * in the `value` column.
 */
class Setting extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'key',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->useDisk('private')->singleFile();
    }

    /**
     * The stored value for $key, or $default if the key doesn't exist.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::query()->where('key', $key)->first()?->value ?? $default;
    }

    /**
     * Create or overwrite the value for $key.
     */
    public static function set(string $key, mixed $value): self
    {
        $setting = static::firstOrNew(['key' => $key]);
        $setting->value = $value;
        $setting->save();

        return $setting;
    }
}
