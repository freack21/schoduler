<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    protected $table = 'pengaturan';
    protected $fillable = ['key', 'value', 'label'];

    /**
     * Get a setting value by key.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value.
     */
    public static function setValue(string $key, mixed $value, ?string $label = null): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, ...($label ? ['label' => $label] : [])]
        );
    }

    /**
     * Get hari aktif as array.
     */
    public static function getHariAktif(): array
    {
        $value = static::getValue('hari_aktif', 'Senin,Selasa,Rabu,Kamis,Jumat');
        return array_map('trim', explode(',', $value));
    }
}
