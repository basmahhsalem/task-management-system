<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class RoleService
{
    public static function getUserRole(int $userId): ?string
    {
        $result = DB::select('EXEC dbo.stp_users_loadById @UserID = :userId', [
            'userId' => $userId
        ]);

        return $result[0]->role ?? null;
    }

    public static function isManager(int $userId): bool
    {
        return self::getUserRole($userId) === 'Manager';
    }

    public static function isEmployee(int $userId): bool
    {
        return self::getUserRole($userId) === 'Employee';
    }
}
