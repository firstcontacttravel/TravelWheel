<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class EncryptedJson implements CastsAttributes
{
    private const KEY = '__travelwheel_encrypted';

    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        $decoded = is_array($value) ? $value : json_decode((string) $value, true);

        if (is_array($decoded) && isset($decoded[self::KEY])) {
            return json_decode(Crypt::decryptString((string) $decoded[self::KEY]), true);
        }

        return $decoded;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        return json_encode([
            self::KEY => Crypt::encryptString(json_encode($value, JSON_THROW_ON_ERROR)),
        ], JSON_THROW_ON_ERROR);
    }
}
