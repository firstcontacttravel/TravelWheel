<?php

namespace App\Enums;

enum VisaEligibilityMode: string
{
    case All = 'all';
    case Rules = 'rules';

    public static function options(): array
    {
        return ['all' => 'All nationalities', 'rules' => 'Use inclusion/exclusion rules'];
    }
}
