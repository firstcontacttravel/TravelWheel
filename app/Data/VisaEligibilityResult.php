<?php

namespace App\Data;

readonly class VisaEligibilityResult
{
    public function __construct(
        public string $status,
        public array $messages = [],
        public array $matchedRuleIds = [],
    ) {}

    public function isEligible(): bool
    {
        return $this->status === 'eligible';
    }
}
