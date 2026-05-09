<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabSetting extends Model
{
    public $timestamps = false;

    protected $fillable = ['key', 'value', 'description'];

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
