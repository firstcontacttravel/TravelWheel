<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * The receipt for something a person did in the admin: who, in which
 * department, what, to which record, with what input. Append-only.
 */
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    /** Form fields never written to the log. */
    private const REDACTED_KEYS = ['password', 'password_confirmation', 'current_password', 'token', 'secret'];

    protected $fillable = [
        'user_id',
        'department_id',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'properties',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public static function record(string $action, string $description, ?Model $subject = null, array $properties = [], ?User $user = null): self
    {
        $user ??= auth()->user();

        return static::create([
            'user_id' => $user?->getKey(),
            'department_id' => $user?->department_id,
            'action' => Str::limit($action, 250, ''),
            'description' => Str::limit($description, 250),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'properties' => self::sanitize($properties) ?: null,
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Keeps what a person typed or picked, drops secrets and uploads, and
     * shortens long text so one action cannot write a megabyte of log.
     */
    private static function sanitize(array $values, int $depth = 0): array
    {
        $clean = [];

        foreach ($values as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::REDACTED_KEYS, true)) {
                $clean[$key] = '[redacted]';

                continue;
            }

            $clean[$key] = match (true) {
                is_array($value) => $depth < 3 ? self::sanitize($value, $depth + 1) : '[nested]',
                is_string($value) => Str::limit($value, 500),
                is_scalar($value), $value === null => $value,
                $value instanceof \DateTimeInterface => $value->format(DATE_ATOM),
                $value instanceof \BackedEnum => $value->value,
                default => '['.class_basename($value).']',
            };
        }

        return $clean;
    }
}
