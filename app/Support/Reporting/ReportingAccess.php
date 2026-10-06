<?php

namespace App\Support\Reporting;

use App\Models\Department;
use App\Models\User;

class ReportingAccess
{
    public static function canView(?User $user): bool
    {
        return (bool) ($user && (
            $user->isAdmin()
            || in_array($user->visa_role, ['administrator', 'finance', 'support'], true)
            || $user->inDepartment(Department::FINANCE, Department::CUSTOMER_SUPPORT)
        ));
    }

    public static function canViewFinancials(?User $user): bool
    {
        return (bool) ($user && (
            $user->isAdmin()
            || in_array($user->visa_role, ['administrator', 'finance'], true)
            || $user->inDepartment(Department::FINANCE)
        ));
    }

    public static function canManage(?User $user): bool
    {
        return (bool) ($user && (
            $user->isAdmin()
            || $user->visa_role === 'administrator'
        ));
    }
}
