<?php

namespace App\Filament\Resources\NotificationOutboxes\Pages;

use App\Filament\Resources\NotificationOutboxes\NotificationOutboxResource;
use Filament\Resources\Pages\ListRecords;

class ListNotificationOutboxes extends ListRecords
{
    protected static string $resource = NotificationOutboxResource::class;
}
