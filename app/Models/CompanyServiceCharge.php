<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A corporate service charge per passenger. With a company it is that
 * company's rate; without one it is the default corporate rate.
 */
class CompanyServiceCharge extends Model
{
    protected $fillable = [
        'company_id',
        'route_category',
        'cabin',
        'amount',
    ];

    protected function casts(): array
    {
        return ['amount' => 'float'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return array<string, float> "route_category.cabin" => amount */
    public static function keyedFor(?int $companyId): array
    {
        return static::query()
            ->when(
                $companyId === null,
                fn ($query) => $query->whereNull('company_id'),
                fn ($query) => $query->where('company_id', $companyId),
            )
            ->get()
            ->mapWithKeys(fn (self $charge): array => [
                $charge->route_category.'.'.$charge->cabin => (float) $charge->amount,
            ])
            ->all();
    }
}
