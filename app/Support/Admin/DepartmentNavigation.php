<?php

namespace App\Support\Admin;

use App\Models\User;
use Filament\Navigation\NavigationGroup;

/**
 * Puts a member of staff's own department at the top of the navigation rail.
 *
 * Nothing is hidden: anyone can claim any booking and escalations cross
 * teams, so every menu stays one hover away. The rail only answers "where is
 * my work" before "where is everything". The CEO, who belongs to every
 * department, keeps the panel's declared order.
 */
class DepartmentNavigation
{
    /**
     * Department slug => the rail groups that department works in, in the
     * order they should appear. A department added later on the Departments
     * screen has none until it is listed here, and sees the normal order.
     */
    public const GROUPS = [
        'flights' => ['Flights'],
        'visas' => ['Visas'],
        'finance' => ['Finance'],
        'customer-support' => ['Customer Support'],
        'ground-airport' => ['Travel Connections', 'Airport Services', 'Air Cargo'],
        'it' => ['System'],
    ];

    /** @return list<string> */
    public static function groupsFor(?User $user): array
    {
        if (! $user || $user->isAdmin() || ! $user->department_id) {
            return [];
        }

        return self::GROUPS[$user->department?->slug] ?? [];
    }

    /**
     * Splits the rail into the person's own groups (in department order)
     * and everything else (in the panel's order).
     *
     * @param  list<NavigationGroup>  $groups
     * @return array{mine: list<NavigationGroup>, others: list<NavigationGroup>}
     */
    public static function split(array $groups, ?User $user): array
    {
        $own = self::groupsFor($user);
        $byLabel = [];
        $others = [];

        foreach ($groups as $group) {
            if (in_array($group->getLabel(), $own, true)) {
                $byLabel[$group->getLabel()] = $group;
            } else {
                $others[] = $group;
            }
        }

        $mine = array_values(array_filter(array_map(fn (string $label) => $byLabel[$label] ?? null, $own)));

        return ['mine' => $mine, 'others' => $others];
    }
}
