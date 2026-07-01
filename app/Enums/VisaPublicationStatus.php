<?php

namespace App\Enums;

enum VisaPublicationStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public static function options(): array
    {
        return ['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'];
    }
}
