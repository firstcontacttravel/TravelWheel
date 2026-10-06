<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A team of staff. Any member can claim work and escalate it, within the
 * department or to another one.
 */
class Department extends Model
{
    public const FINANCE = 'finance';

    public const VISAS = 'visas';

    public const CUSTOMER_SUPPORT = 'customer-support';

    /** The two teams Linear's free plan allows. */
    public const LINEAR_TEAMS = [
        'travelwheel' => 'Travelwheel',
        'it' => 'IT department',
    ];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'linear_team',
        'linear_label',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
