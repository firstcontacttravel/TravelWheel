<?php

namespace App\Models\Concerns;

trait BumpsVisaProductVersion
{
    protected static function bootBumpsVisaProductVersion(): void
    {
        static::saved(fn ($model) => $model->visaProduct()->increment('version'));
        static::deleted(fn ($model) => $model->visaProduct()->increment('version'));
    }
}
