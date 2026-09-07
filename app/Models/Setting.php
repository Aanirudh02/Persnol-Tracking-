<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group', 'description'];

    public static function getVal(string $key, mixed $default = null): mixed
    {
        $setting = self::where('key', $key)->first();
        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {
            'integer' => (int) $setting->value,
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    public static function setVal(string $key, mixed $value, string $type = 'string', string $group = 'general', ?string $desc = null): self
    {
        $storeValue = is_array($value) ? json_encode($value) : (string) $value;

        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $storeValue,
                'type' => $type,
                'group' => $group,
                'description' => $desc ?? $key,
            ]
        );
    }
}
