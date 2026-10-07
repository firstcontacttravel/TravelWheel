<?php

namespace App\Support\Admin;

use App\Models\ActivityLog;
use Filament\Actions\Action;
use Filament\Actions\Events\ActionCalled;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

/**
 * Every action a person runs in the admin leaves an ActivityLog row: who,
 * which department, which action, on which record, with what form input.
 *
 * Listens for Filament's ActionCalled event rather than being written into
 * each action, so a new action is on the record without anyone remembering
 * to add it. The event only fires once an action has run; one that fails
 * validation, is halted or is cancelled at the confirmation leaves no row.
 */
class ActivityRecorder
{
    public static function register(): void
    {
        // Dispatched by name with the action as its payload, not as an
        // ActionCalled instance, so the listener receives the Action itself.
        Event::listen(ActionCalled::class, fn (Action $action) => self::record($action));
    }

    public static function record(Action $action): void
    {
        if (! auth()->check()) {
            return;
        }

        $record = $action->getRecord();
        $subject = $record instanceof Model ? $record : null;
        $properties = ['input' => $action->getData()];

        $selected = $action->canAccessSelectedRecords() ? $action->getSelectedRecords() : null;
        if ($selected !== null && $selected->isNotEmpty()) {
            $properties['records'] = $selected->map(fn ($model) => $model instanceof Model ? $model->getKey() : null)->filter()->values()->all();
        }

        $label = trim(strip_tags((string) ($action->getLabel() ?? $action->getName())));
        $reference = $subject ? self::reference($subject) : null;

        ActivityLog::record(
            action: (string) $action->getName(),
            description: $reference ? "{$label} · {$reference}" : $label,
            subject: $subject,
            properties: array_filter($properties, fn ($value) => $value !== [] && $value !== null),
        );
    }

    public static function reference(Model $subject): string
    {
        foreach (['booking_ref', 'reference', 'name', 'email'] as $attribute) {
            if (filled($subject->getAttribute($attribute))) {
                return (string) $subject->getAttribute($attribute);
            }
        }

        return class_basename($subject).' #'.$subject->getKey();
    }
}
