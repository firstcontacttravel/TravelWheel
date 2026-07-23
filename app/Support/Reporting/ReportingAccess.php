<?php

namespace App\Support\Reporting;

use App\Models\User;

class ReportingAccess
{
    public static function canView(?User $user): bool
    {
        return (bool) ($user && (
            $user->is_admin
            || in_array($user->visa_role, ['administrator', 'finance', 'support'], true)
            || self::isConfiguredAdmin($user)
        ));
    }

    public static function canViewFinancials(?User $user): bool
    {
        return (bool) ($user && (
            $user->is_admin
            || in_array($user->visa_role, ['administrator', 'finance'], true)
            || self::isConfiguredAdmin($user)
        ));
    }

    public static function canManage(?User $user): bool
    {
        return (bool) ($user && (
            $user->is_admin
            || $user->visa_role === 'administrator'
            || self::isConfiguredAdmin($user)
        ));
    }

    private static function isConfiguredAdmin(User $user): bool
    {
        return collect(explode(',', (string) env('ADMIN_EMAILS', '')))
            ->map(fn (string $email): string => strtolower(trim($email)))
            ->filter()
            ->contains(strtolower($user->email));
    }
}
