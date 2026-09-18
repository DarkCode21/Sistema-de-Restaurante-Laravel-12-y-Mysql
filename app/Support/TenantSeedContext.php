<?php

namespace App\Support;

use Closure;

final class TenantSeedContext
{
    private static ?int $companyId = null;
    private static ?int $branchId = null;

    public static function run(int $companyId, int $branchId, Closure $callback): mixed
    {
        [$previousCompanyId, $previousBranchId] = [self::$companyId, self::$branchId];
        self::$companyId = $companyId;
        self::$branchId = $branchId;

        try {
            return $callback();
        } finally {
            self::$companyId = $previousCompanyId;
            self::$branchId = $previousBranchId;
        }
    }

    public static function companyId(): ?int
    {
        return self::$companyId;
    }

    public static function branchId(): ?int
    {
        return self::$branchId;
    }
}
