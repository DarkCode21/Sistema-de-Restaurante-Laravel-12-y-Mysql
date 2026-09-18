<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCompanyPermissionContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $companyId = $request->session()->get('company_id')
                ?: $request->user()->companies()->value('companies.id')
                ?: Company::query()->where('is_active', true)->value('id');

            setPermissionsTeamId($companyId);
            $request->user()->unsetRelation('roles')->unsetRelation('permissions');
        }

        return $next($request);
    }
}
