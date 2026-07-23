<?php

namespace App\Contracts;

use Illuminate\Support\LazyCollection;

interface ReportingSourceAdapter
{
    public function sourceType(): string;

    public function available(): bool;

    /** @return LazyCollection<int, array<string, mixed>> */
    public function facts(): LazyCollection;
}
