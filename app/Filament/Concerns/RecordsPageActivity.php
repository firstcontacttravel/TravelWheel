<?php

namespace App\Filament\Concerns;

use App\Models\ActivityLog;
use App\Support\Admin\ActivityRecorder;
use Filament\Resources\Pages\CreateRecord;

/**
 * Logs what Create and Edit pages save. Saving a page is not a Filament action,
 * so ActivityRecorder never sees it, and these pages are where prices, rates
 * and settings change.
 *
 * Wraps callHook() rather than defining afterSave()/afterCreate(), so a page
 * can keep its own hooks without the two colliding.
 */
trait RecordsPageActivity
{
    /** @var array<string, mixed> */
    private array $activityBefore = [];

    protected function callHook(string $hook): void
    {
        if ($hook === 'beforeSave') {
            $this->activityBefore = $this->record->getAttributes();
        }

        parent::callHook($hook);

        if ($hook === 'afterCreate' && $this instanceof CreateRecord) {
            $this->recordPageActivity('created', 'Created', [
                'values' => collect($this->record->getAttributes())->except(['id', 'created_at', 'updated_at', 'remember_token'])->all(),
            ]);
        }

        if ($hook === 'afterSave') {
            $changes = collect($this->record->getChanges())->except(['updated_at', 'remember_token']);

            if ($changes->isNotEmpty()) {
                $this->recordPageActivity('updated', 'Updated', [
                    'before' => $changes->map(fn ($value, string $key) => $this->activityBefore[$key] ?? null)->all(),
                    'after' => $changes->all(),
                ]);
            }
        }
    }

    private function recordPageActivity(string $verb, string $label, array $properties): void
    {
        $resource = static::getResource();
        $title = $resource::hasRecordTitle() ? (string) $resource::getRecordTitle($this->record) : ActivityRecorder::reference($this->record);

        ActivityLog::record(
            action: str(class_basename($this->record))->snake().'.'.$verb,
            description: "{$label} ".$resource::getModelLabel().' · '.$title,
            subject: $this->record,
            properties: $properties,
        );
    }
}
