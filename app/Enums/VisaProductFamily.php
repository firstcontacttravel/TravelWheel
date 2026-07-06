<?php

namespace App\Enums;

enum VisaProductFamily: string
{
    case Standard = 'standard';
    case VisaOnArrival = 'voa';

    public static function options(): array
    {
        return ['standard' => 'Standard visa', 'voa' => 'Nigerian Business Visa'];
    }
}
